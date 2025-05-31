<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Humaidem\FilamentMapPicker\Fields\OSMMap;
use Auth;
use Afsakar\LeafletMapPicker\LeafletMapPicker;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;


class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 5;

    use \App\Traits\HasNavigationBadge;

    protected static ?string $navigationGroup = 'Manajamen Presensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('💡 Informasi Pegawai')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->label('Nama Pegawai')
                            ->disabled()
                            ->formatStateUsing(fn ($record) => $record?->user?->name ?? $record?->user_id),
                        
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('schedule_start_time')
                                    ->label('Shift mulai bekerja')
                                    ->disabled(),
                                Forms\Components\TextInput::make('schedule_end_time')
                                    ->label('Shift akhir bekerja')
                                    ->disabled(),
                                Forms\Components\TimePicker::make('start_time')
                                    ->label('Waktu jam datang')
                                    ->displayFormat('H:i')
                                    ->native(false)
                                    ->seconds(false)
                                    ->required(),
                                Forms\Components\TimePicker::make('end_time')
                                    ->label('Waktu jam pulang')
                                    ->displayFormat('H:i')
                                    ->native(false)
                                    ->seconds(false)
                                    ->required(),

                                Forms\Components\Select::make('overdue')
                                    ->label('Status Datang')
                                    ->options([
                                        Attendance::OVERDUE_ON_TIME => 'TW',
                                        Attendance::OVERDUE_TL_1 => 'TL 1',
                                        Attendance::OVERDUE_TL_2 => 'TL 2',
                                        Attendance::OVERDUE_NOT_PRESENT => 'TH',
                                    ])
                                    ->default(Attendance::OVERDUE_ON_TIME),

                                Forms\Components\TextInput::make('overdue_minutes')
                                    ->label('Keterlambatan (Menit)')
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\Select::make('return')
                                    ->label('Status Pulang')
                                    ->options([
                                        Attendance::RETURN_ON_TIME => 'TW',
                                        Attendance::RETURN_PSW_1 => 'PSW 1',
                                        Attendance::RETURN_PSW_2 => 'PSW 2',
                                        Attendance::RETURN_NOT_PRESENT => 'TH',
                                    ])
                                    ->default(Attendance::RETURN_ON_TIME),

                                Forms\Components\TextInput::make('return_minutes')
                                    ->label('Pulang Awal (Menit)')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('work_duration')
                                    ->label('Durasi Kerja (Menit)')
                                    ->numeric()
                                    ->default(0),

                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Check In Location')
                            ->schema([
                                LeafletMapPicker::make('start_location')
                                    ->label('Check-in Location')
                                    ->height('200px')
                                    ->dehydrated(false)
                                    ->defaultLocation(function ($record) {
                                            return [
                                                $record->start_latitude,
                                                $record->start_longitude,
                                            ];
                                        })
                                    ->defaultZoom(8)
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
                                    ->afterStateHydrated(function (Forms\Set $set, $state, $record) {
                                        if ($record && $record->latitude && $record->longitude) {
                                            $set('start_location', [
                                                'lat' => $record->latitude,
                                                'lng' => $record->longitude,
                                            ]);
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if (is_array($state)) {
                                            $set('start_latitude', $state['lat']);
                                            $set('start_longitude', $state['lng']);
                                        }
                                    }),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('start_latitude')
                                            ->label('Checkin Latitude')
                                            ->numeric()
                                            ->prefixIcon('heroicon-o-map-pin')
                                            ->prefixIconColor('success'),
                                        Forms\Components\TextInput::make('start_longitude')
                                            ->label('Checkin Longitude')
                                            ->numeric()
                                            ->prefixIcon('heroicon-o-map-pin')
                                            ->prefixIconColor('success'),
                                    ])
                                    ->columns(2),
                            ])
                            ->columnSpan(1),

                        Forms\Components\Section::make('Check Out Location')
                            ->schema([
                                LeafletMapPicker::make('end_location')
                                    ->label('Check-out Location')
                                    ->height('200px')
                                    ->dehydrated(false)
                                    ->defaultLocation(function ($record) {
                                            return [
                                                $record->end_latitude,
                                                $record->end_longitude,
                                            ];
                                        })
                                    ->defaultZoom(8)
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
                                    ->afterStateHydrated(function (Forms\Set $set, $state, $record) {
                                        if ($record && $record->latitude && $record->longitude) {
                                            $set('end_location', [
                                                'lat' => $record->latitude,
                                                'lng' => $record->longitude,
                                            ]);
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if (is_array($state)) {
                                            $set('end_latitude', $state['lat']);
                                            $set('end_longitude', $state['lng']);
                                        }
                                    }),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('end_latitude')
                                            ->label('Checkout Latitude')
                                            ->numeric()
                                            ->prefixIcon('heroicon-o-map-pin')
                                            ->prefixIconColor('danger'),
                                        Forms\Components\TextInput::make('end_longitude')
                                            ->label('Checkout Longitude')
                                            ->numeric()
                                            ->prefixIcon('heroicon-o-map-pin')
                                            ->prefixIconColor('danger'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn ($record) => $record && ($record->end_latitude || $record->end_longitude)),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::getTableColumns())
            ->filters(self::getTableFilters())
            ->actions(self::getTableActions())
            ->bulkActions(self::getTableBulkActions())
            ->defaultSort('created_at', 'desc');
    }

    protected static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('user.name')
                ->label('Pegawai')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Tanggal')
                ->date()
                ->sortable(),

            Tables\Columns\TextColumn::make('start_time')
                ->label('Waktu Datang')
                ->formatStateUsing(fn ($state) => $state ?? ''),

            Tables\Columns\TextColumn::make('end_time')
                ->label('Waktu Pulang')
                ->formatStateUsing(fn ($state) => $state ?? ''),

            Tables\Columns\BadgeColumn::make('overdue')
                ->label('Status Datang')
                ->sortable()
                ->badge()
                ->formatStateUsing(fn ($record) => $record->overdue_status_label)
                ->color(fn ($record) => $record->overdue_status_color),

            Tables\Columns\TextColumn::make('overdue_minutes')
                ->label('Keterlambatan (Menit)')
                ->sortable()
                ->formatStateUsing(fn ($state, $record) => self::formatMinutes($state, $record, 'overdue')),

            Tables\Columns\BadgeColumn::make('return')
                ->label('Status Pulang')
                ->sortable()
                ->badge()
                ->formatStateUsing(fn ($record) => $record->return_status_label)
                ->color(fn ($record) => $record->return_status_color),

            Tables\Columns\TextColumn::make('return_minutes')
                ->label('Pulang Awal (Menit)')
                ->sortable()
                ->formatStateUsing(fn ($state, $record) => self::formatMinutes($state, $record, 'return')),
            
            Tables\Columns\TextColumn::make('work_duration')
                ->label('Durasi Kerja (Menit)')
                ->sortable()
                ->formatStateUsing(fn ($state, $record) => self::formatMinutes($state, $record, 'work_duration')),
            
            Tables\Columns\ImageColumn::make('start_attendance_photo')
                ->label('Foto Datang')
                ->circular(),

            Tables\Columns\ImageColumn::make('end_attendance_photo')
                ->label('Foto Pulang')
                ->circular(),

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
            Tables\Filters\SelectFilter::make('overdue')
                ->label('Status Datang')
                ->options([
                    Attendance::OVERDUE_ON_TIME => 'TW',
                    Attendance::OVERDUE_TL_1 => 'TL 1',
                    Attendance::OVERDUE_TL_2 => 'TL 2',
                    Attendance::OVERDUE_NOT_PRESENT => 'TH',
                ]),
            Tables\Filters\SelectFilter::make('return')
                ->label('Status Pulang')
                ->options([
                    Attendance::RETURN_ON_TIME => 'TW',
                    Attendance::RETURN_PSW_1 => 'PSW 1',
                    Attendance::RETURN_PSW_2 => 'PSW 2',
                    Attendance::RETURN_NOT_PRESENT => 'TH',
                ]),
            Tables\Filters\SelectFilter::make('created_at')
                ->label('Periode Waktu')
                ->options([
                    'today' => 'Hari ini',
                    'yesterday' => 'Kemarin',
                    'last_week' => 'Minggu lalu',
                    'last_month' => 'Bulan lalu',
                    'three_months' => '3 bulan terakhir',
                    'six_months' => '6 bulan terakhir',
                    'this_year' => 'Tahun ini',
                ])
                ->query(fn (Builder $query, array $data): Builder => self::handleDateFilter($query, $data)),
        ];

        if (Auth::user()->hasRole('super_admin')) {
            $filters[] = Tables\Filters\SelectFilter::make('user_id')
                ->label('Pegawai')
                ->relationship('user', 'name')
                ->searchable()
                ->preload();
        }

        return $filters;
    }

    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->color('gray')
                ->button()
                ->icon('heroicon-o-eye'),
            Tables\Actions\EditAction::make()
                ->color('primary')
                ->button()
                ->icon('heroicon-o-pencil-square'),
            Tables\Actions\DeleteAction::make()
                ->color('danger')
                ->button()
                ->icon('heroicon-o-trash'),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }

    protected static function formatMinutes($state, $record, string $type): string
    {
        if (!$record) {
            return '-';
        }
        // Untuk kolom work_duration, tampilkan 0 menit jika 0
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

    protected static function handleDateFilter(Builder $query, array $data): Builder
    {
        if (empty($data['value'])) {
            return $query;
        }

        $now = Carbon::now();

        return match ($data['value']) {
            'today' => $query->whereDate('created_at', $now->today()),
            'yesterday' => $query->whereDate('created_at', $now->copy()->subDay()),
            'last_week' => $query->whereBetween('created_at', [
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
            ]),
            'last_month' => $query->whereBetween('created_at', [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ]),
            'three_months' => $query->whereBetween('created_at', [
                $now->copy()->subMonths(3)->startOfDay(),
                $now->copy()->endOfDay(),
            ]),
            'six_months' => $query->whereBetween('created_at', [
                $now->copy()->subMonths(6)->startOfDay(),
                $now->copy()->endOfDay(),
            ]),
            'this_year' => $query->whereBetween('created_at', [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
            ]),
            default => $query,
        };
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

        return $query;
    }

}
