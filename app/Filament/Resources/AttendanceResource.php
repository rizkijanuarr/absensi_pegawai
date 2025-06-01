<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Afsakar\LeafletMapPicker\LeafletMapPicker;

class AttendanceResource extends Resource
{
    private const LOG_CHANNEL = 'attendance_management';
    private const MAP_HEIGHT = '200px';
    private const DEFAULT_ZOOM = 8;

    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationGroup = 'Manajamen Presensi';

    use \App\Traits\HasNavigationBadge;

    protected static array $periodOptions = [
        'today' => 'Hari ini',
        'yesterday' => 'Kemarin',
        'last_week' => 'Minggu lalu',
        'last_month' => 'Bulan lalu',
        'three_months' => '3 bulan terakhir',
        'six_months' => '6 bulan terakhir',
        'this_year' => 'Tahun ini',
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                self::getEmployeeInfoSection(),
                self::getLocationSection(),
            ]);
    }

    private static function getEmployeeInfoSection(): Section
    {
        return Section::make('💡 Informasi Pegawai')
            ->schema([
                self::getEmployeeField(),
                self::getScheduleFields(),
            ])
            ->columnSpanFull();
    }

    private static function getEmployeeField(): TextInput
    {
        return TextInput::make('user_id')
            ->label('Nama Pegawai')
            ->disabled()
            ->formatStateUsing(fn ($record) => $record?->user?->name ?? $record?->user_id);
    }

    private static function getScheduleFields(): Group
    {
        return Group::make()
            ->schema([
                self::getScheduleTimeFields(),
                self::getAttendanceStatusFields(),
            ])
            ->columns(2);
    }

    private static function getScheduleTimeFields(): Group
    {
        return Group::make()
            ->schema([
                TextInput::make('schedule_start_time')
                    ->label('Shift mulai bekerja')
                    ->disabled(),
                TextInput::make('schedule_end_time')
                    ->label('Shift akhir bekerja')
                    ->disabled(),
                TimePicker::make('start_time')
                    ->label('Waktu jam datang')
                    ->displayFormat('H:i')
                    ->native(false)
                    ->seconds(false)
                    ->required(),
                TimePicker::make('end_time')
                    ->label('Waktu jam pulang')
                    ->displayFormat('H:i')
                    ->native(false)
                    ->seconds(false)
                    ->required(),
            ]);
    }

    private static function getAttendanceStatusFields(): Group
    {
        return Group::make()
            ->schema([
                self::getOverdueFields(),
                self::getReturnFields(),
                TextInput::make('work_duration')
                    ->label('Durasi Kerja (Menit)')
                    ->numeric()
                    ->default(0),
            ]);
    }

    private static function getOverdueFields(): Group
    {
        return Group::make()
            ->schema([
                Select::make('overdue')
                    ->label('Status Datang')
                    ->options([
                        Attendance::OVERDUE_ON_TIME => 'TW',
                        Attendance::OVERDUE_TL_1 => 'TL 1',
                        Attendance::OVERDUE_TL_2 => 'TL 2',
                        Attendance::OVERDUE_NOT_PRESENT => 'TH',
                    ])
                    ->default(Attendance::OVERDUE_ON_TIME),
                TextInput::make('overdue_minutes')
                    ->label('Keterlambatan (Menit)')
                    ->numeric()
                    ->default(0),
            ]);
    }

    private static function getReturnFields(): Group
    {
        return Group::make()
            ->schema([
                Select::make('return')
                    ->label('Status Pulang')
                    ->options([
                        Attendance::RETURN_ON_TIME => 'TW',
                        Attendance::RETURN_PSW_1 => 'PSW 1',
                        Attendance::RETURN_PSW_2 => 'PSW 2',
                        Attendance::RETURN_NOT_PRESENT => 'TH',
                    ])
                    ->default(Attendance::RETURN_ON_TIME),
                TextInput::make('return_minutes')
                    ->label('Pulang Awal (Menit)')
                    ->numeric()
                    ->default(0),
            ]);
    }

    private static function getLocationSection(): Group
    {
        return Group::make()
            ->schema([
                self::getCheckInLocationSection(),
                self::getCheckOutLocationSection(),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    private static function getCheckInLocationSection(): Section
    {
        return Section::make('Check In Location')
            ->schema([
                self::getMapField('start'),
                self::getCoordinateFields('start'),
            ])
            ->columnSpan(1);
    }

    private static function getCheckOutLocationSection(): Section
    {
        return Section::make('Check Out Location')
            ->schema([
                self::getMapField('end'),
                self::getCoordinateFields('end'),
            ])
            ->columnSpan(1);
    }

    private static function getMapField(string $type): LeafletMapPicker
    {
        return LeafletMapPicker::make("{$type}_location")
            ->label("Check-{$type} Location")
            ->height(self::MAP_HEIGHT)
            ->dehydrated(false)
            ->defaultLocation(function ($record) use ($type) {
                return [
                    $record->{"{$type}_latitude"},
                    $record->{"{$type}_longitude"},
                ];
            })
            ->defaultZoom(self::DEFAULT_ZOOM)
            ->draggable()
            ->clickable()
            ->myLocationButtonLabel('My Location')
            ->tileProvider('google')
            ->customTiles([
                'mapbox' => [
                    'url' => 'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}',
                    'options' => [
                        'attribution' => '&copy; <a href="https://www.mapbox.com/">Mapbox</a>',
                        'id' => 'mapbox/streets-v11',
                        'maxZoom' => 19,
                        'accessToken' => 'pk.eyJ1Ijoicmlza2kxIiwiYSI6ImNtYjZobTV2OTAwdGEycnNneW40bnl1NDgifQ.ERemFCNO0wv2gwRjFoppqg',
                    ]
                ]
            ])
            ->customMarker([
                'iconUrl' => asset('pin.png'),
                'iconSize' => [38, 38],
                'iconAnchor' => [19, 38],
                'popupAnchor' => [0, -38]
            ])
            ->afterStateHydrated(function (Forms\Set $set, $state, $record) use ($type) {
                if ($record && $record->{"{$type}_latitude"} && $record->{"{$type}_longitude"}) {
                    $set("{$type}_location", [
                        'lat' => $record->{"{$type}_latitude"},
                        'lng' => $record->{"{$type}_longitude"},
                    ]);
                }
            })
            ->afterStateUpdated(function ($state, Forms\Set $set) use ($type) {
                if (is_array($state)) {
                    $set("{$type}_latitude", $state['lat']);
                    $set("{$type}_longitude", $state['lng']);
                }
            });
    }

    private static function getCoordinateFields(string $type): Group
    {
        return Group::make()
            ->schema([
                TextInput::make("{$type}_latitude")
                    ->label("Check{$type} Latitude")
                    ->numeric()
                    ->prefixIcon('heroicon-o-map-pin')
                    ->prefixIconColor($type === 'start' ? 'success' : 'danger'),
                TextInput::make("{$type}_longitude")
                    ->label("Check{$type} Longitude")
                    ->numeric()
                    ->prefixIcon('heroicon-o-map-pin')
                    ->prefixIconColor($type === 'start' ? 'success' : 'danger'),
            ])
            ->columns(2)
            ->visible(fn ($record) => $type === 'start' || ($record && ($record->end_latitude || $record->end_longitude)));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::getTableColumns())
            ->filters(self::getTableFilters())
            ->actions(self::getTableActions())
            ->bulkActions(self::getTableBulkActions())
            ->headerActions(self::getTableHeaderActions())
            ->defaultSort('created_at', 'desc');
    }

    protected static function getTableColumns(): array
    {
        return array_merge(
            [self::getEmployeeColumn()],
            [self::getDateColumn()],
            self::getTimeColumns(),
            self::getStatusColumns(),
            self::getDurationColumns(),
            self::getPhotoColumns(),
            self::getTimestampColumns()
        );
    }

    private static function getEmployeeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('user.name')
            ->label('Pegawai')
            ->searchable()
            ->sortable();
    }

    private static function getDateColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->label('Tanggal')
            ->date()
            ->sortable();
    }

    private static function getTimeColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('start_time')
                ->label('Waktu Datang')
                ->formatStateUsing(fn ($state) => $state ?? ''),
            Tables\Columns\TextColumn::make('end_time')
                ->label('Waktu Pulang')
                ->formatStateUsing(fn ($state) => $state ?? ''),
        ];
    }

    private static function getStatusColumns(): array
    {
        return [
            Tables\Columns\BadgeColumn::make('overdue')
                ->label('Status Datang')
                ->sortable()
                ->badge()
                ->formatStateUsing(fn ($record) => $record->overdue_status_label)
                ->color(fn ($record) => $record->overdue_status_color),
            Tables\Columns\BadgeColumn::make('return')
                ->label('Status Pulang')
                ->sortable()
                ->badge()
                ->formatStateUsing(fn ($record) => $record->return_status_label)
                ->color(fn ($record) => $record->return_status_color),
        ];
    }

    private static function getDurationColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('overdue_minutes')
                ->label('Keterlambatan (Menit)')
                ->sortable()
                ->formatStateUsing(fn ($state, $record) => self::formatMinutes($state, $record, 'overdue')),
            Tables\Columns\TextColumn::make('return_minutes')
                ->label('Pulang Awal (Menit)')
                ->sortable()
                ->formatStateUsing(fn ($state, $record) => self::formatMinutes($state, $record, 'return')),
            Tables\Columns\TextColumn::make('work_duration')
                ->label('Durasi Kerja (Menit)')
                ->sortable()
                ->formatStateUsing(fn ($state, $record) => self::formatMinutes($state, $record, 'work_duration')),
        ];
    }

    private static function getPhotoColumns(): array
    {
        return [
            Tables\Columns\ImageColumn::make('start_attendance_photo')
                ->label('Foto Datang')
                ->circular(),
            Tables\Columns\ImageColumn::make('end_attendance_photo')
                ->label('Foto Pulang')
                ->circular(),
        ];
    }

    private static function getTimestampColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('deleted_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        $filters = [
            self::getOverdueFilter(),
            self::getReturnFilter(),
            self::getPeriodFilter(),
        ];

        if (Auth::user()->hasRole('super_admin')) {
            $filters[] = self::getEmployeeFilter();
        }

        return $filters;
    }

    private static function getOverdueFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('overdue')
            ->label('Status Datang')
            ->options([
                Attendance::OVERDUE_ON_TIME => 'TW',
                Attendance::OVERDUE_TL_1 => 'TL 1',
                Attendance::OVERDUE_TL_2 => 'TL 2',
                Attendance::OVERDUE_NOT_PRESENT => 'TH',
            ]);
    }

    private static function getReturnFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('return')
            ->label('Status Pulang')
            ->options([
                Attendance::RETURN_ON_TIME => 'TW',
                Attendance::RETURN_PSW_1 => 'PSW 1',
                Attendance::RETURN_PSW_2 => 'PSW 2',
                Attendance::RETURN_NOT_PRESENT => 'TH',
            ]);
    }

    private static function getPeriodFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('created_at')
            ->label('Periode Waktu')
            ->options(self::$periodOptions)
            ->query(fn (Builder $query, array $data): Builder =>
                empty($data['value']) ? $query : self::applyPeriodFilter($query, $data['value'])
            );
    }

    private static function getEmployeeFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('user_id')
            ->label('Pegawai')
            ->relationship('user', 'name')
            ->searchable()
            ->preload();
    }

    protected static function getTableActions(): array
    {
        return [
            self::getViewAction(),
            self::getEditAction(),
            self::getDeleteAction(),
        ];
    }

    private static function getViewAction(): Tables\Actions\ViewAction
    {
        return Tables\Actions\ViewAction::make()
            ->color('gray')
            ->button()
            ->icon('heroicon-o-eye')
            ->before(function (Attendance $record) {
                Log::channel(self::LOG_CHANNEL)->info('Attendance viewed', [
                    'attendance_id' => $record->id,
                    'user_id' => $record->user_id,
                    'date' => $record->created_at->format('Y-m-d'),
                    'start_time' => $record->start_time,
                    'end_time' => $record->end_time
                ]);
            });
    }

    private static function getEditAction(): Tables\Actions\EditAction
    {
        return Tables\Actions\EditAction::make()
            ->color('primary')
            ->button()
            ->icon('heroicon-o-pencil-square')
            ->before(function (Attendance $record) {
                Log::channel(self::LOG_CHANNEL)->info('Attendance edit started', [
                    'attendance_id' => $record->id,
                    'user_id' => $record->user_id,
                    'date' => $record->created_at->format('Y-m-d'),
                    'start_time' => $record->start_time,
                    'end_time' => $record->end_time
                ]);
            })
            ->after(function (Attendance $record) {
                Log::channel(self::LOG_CHANNEL)->info('Attendance updated', [
                    'attendance_id' => $record->id,
                    'user_id' => $record->user_id,
                    'date' => $record->created_at->format('Y-m-d'),
                    'start_time' => $record->start_time,
                    'end_time' => $record->end_time
                ]);
            });
    }

    private static function getDeleteAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->color('danger')
            ->button()
            ->icon('heroicon-o-trash')
            ->before(function (Attendance $record) {
                Log::channel(self::LOG_CHANNEL)->info('Attendance deletion started', [
                    'attendance_id' => $record->id,
                    'user_id' => $record->user_id,
                    'date' => $record->created_at->format('Y-m-d'),
                    'start_time' => $record->start_time,
                    'end_time' => $record->end_time
                ]);
            })
            ->after(function (Attendance $record) {
                Log::channel(self::LOG_CHANNEL)->info('Attendance deleted', [
                    'attendance_id' => $record->id,
                    'user_id' => $record->user_id,
                    'date' => $record->created_at->format('Y-m-d'),
                    'start_time' => $record->start_time,
                    'end_time' => $record->end_time
                ]);
            });
    }

    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }

    public static function getTableHeaderActions(): array
    {
        $formFields = [
            Forms\Components\Select::make('period')
                ->label('Periode')
                ->options(self::$periodOptions)
                ->required(),
        ];

        if (Auth::user()->hasRole('super_admin')) {
            $formFields[] = Forms\Components\Select::make('employee_id')
                ->label('Pegawai')
                ->options(User::pluck('name', 'id'))
                ->searchable();
        }

        return [
            Tables\Actions\Action::make('print')
                ->label('Export PDF')
                ->button()
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->action(function (array $data) {
                    $query = Attendance::query();
                    $selectedPeriodLabel = self::$periodOptions[$data['period']] ?? '-';

                    if (isset($data['period'])) {
                        self::applyPeriodFilter($query, $data['period']);
                    }

                    if (Auth::user()->hasRole('super_admin') && isset($data['employee_id'])) {
                        $query->where('user_id', $data['employee_id']);
                    } else {
                        $query->where('user_id', Auth::id());
                    }

                    $attendances = $query->with('user')->get();

                    Log::channel(self::LOG_CHANNEL)->info('Attendance PDF exported', [
                        'period' => $selectedPeriodLabel,
                        'user_id' => Auth::id(),
                        'is_super_admin' => Auth::user()->hasRole('super_admin'),
                        'employee_id' => $data['employee_id'] ?? null,
                        'count' => $attendances->count()
                    ]);

                    $pdf = Pdf::loadView('pdf.attendance.print-attendance', [
                        'attendances' => $attendances,
                        'periode' => $selectedPeriodLabel,
                    ]);

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->stream();
                    }, 'attendance-' . now()->format('Y-m-d_H-i-s') . '.pdf');
                })
                ->form($formFields),
        ];
    }

    protected static function applyPeriodFilter($query, $period)
    {
        return $query->where(function ($q) use ($period) {
            match ($period) {
                'yesterday' => $q->whereDate('created_at', now()->subDay()),
                'today' => $q->whereDate('created_at', today()),
                'last_week' => $q->whereBetween('created_at', [now()->subWeek(), now()]),
                'last_month' => $q->whereBetween('created_at', [now()->subMonth(), now()]),
                'three_months' => $q->whereBetween('created_at', [now()->subMonths(3), now()]),
                'six_months' => $q->whereBetween('created_at', [now()->subMonths(6), now()]),
                'this_year' => $q->whereYear('created_at', now()->year),
                default => $q
            };
        });
    }

    protected static function formatMinutes($state, $record, string $type): string
    {
        if (!$record) {
            return '-';
        }

        if ($type === 'work_duration') {
            if ($state === 0 || $state === '0' || $state === null) {
                return '0 menit';
            }
        } else {
            if (!$record->{$type}) {
                return $type === 'overdue' ? 'null' : '-';
            }
            if ($state === 0) {
                return '0 menit';
            }
            if (!$state) {
                return $type === 'overdue' ? 'null' : '-';
            }
        }

        $hours = floor($state / 60);
        $minutes = $state % 60;

        if ($hours > 0) {
            return $minutes > 0 
                ? "{$hours} jam {$minutes} menit"
                : "{$hours} jam";
        }

        return "{$minutes} menit";
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['schedule.shift', 'user']);

        $user = Auth::user();
        $isSuperAdmin = $user?->hasRole('super_admin');

        if (!$isSuperAdmin) {
            $query->where('user_id', $user->id);
        }

        Log::channel(self::LOG_CHANNEL)->info('Attendance query executed', [
            'user_id' => $user->id,
            'is_super_admin' => $isSuperAdmin
        ]);

        return $query;
    }
}
