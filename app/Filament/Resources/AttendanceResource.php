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

// TODO LIST!!!
/*  
    1. EDIT DI REKAP ATTENDANCE DI SUPER ADMIN ENABLE
    2. DI HALAMAN PRESENSI BISA MELAKUKAN ENABLE DAN DISABLE KAMERANYA DILAKUKAN DI SUPER ADMIN 
    3. FITUR OFFICE LATITUDE & LONGITUDE BISA DI ISI KEMUDIAN SEARCH
*/

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
                // Section 1: Informasi Pegawai
                Forms\Components\Section::make('Informasi Pegawai')
                    ->description('💡 Informasi Waktu AM 00.00 - 11.59 | Waktu PM 12.00 - 23.59')
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
                                    ->suffixIconColor('success'), 
                                Forms\Components\TimePicker::make('end_time')
                                    ->label('Waktu jam pulang')
                                    ->suffixIconColor('danger'), 
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),

                // Section 2: Status Presensi
                Forms\Components\Section::make('Status Presensi')
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('overdue')
                                    ->label('Status Datang')
                                    ->options([
                                        'on_time' => 'Tepat Waktu',
                                        'tl_1' => 'Terlambat 1-60 Menit',
                                        'tl_2' => 'Terlambat > 60 Menit',
                                        'not_present' => 'Tidak Hadir',
                                    ])
                                    ->default('on_time')
                                    ->required(),

                                Forms\Components\TextInput::make('overdue_minutes')
                                    ->label('Keterlambatan (Menit)')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Forms\Components\Select::make('return')
                                    ->label('Status Pulang')
                                    ->options([
                                        'on_time' => 'Tepat Waktu',
                                        'psw_1' => 'Pulang Awal 1-30 Menit',
                                        'psw_2' => 'Pulang Awal 31-60 Menit',
                                        'not_present' => 'Tidak Hadir',
                                    ])
                                    ->default('on_time')
                                    ->required(),

                                Forms\Components\TextInput::make('return_minutes')
                                    ->label('Pulang Awal (Menit)')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Forms\Components\Select::make('overall_status')
                                    ->label('Status Keseluruhan')
                                    ->options([
                                        'perfect' => 'Sempurna',
                                        'overdue_only' => 'Hanya Terlambat',
                                        'return_only' => 'Hanya Pulang Awal',
                                        'red_flag' => 'Terlambat & Pulang Awal',
                                        'absent' => 'Tidak Hadir',
                                    ])
                                    ->required(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),

                // Section 2: Check In & Check Out Location (Side by Side)
                Forms\Components\Group::make()
                    ->schema([
                        // Check In Location (Kiri)
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
                                                'accessToken' => 'pk.eyJ1Ijoicml6a2kxIiwiYSI6ImNtYjZobTV2OTAwdGEycnNneW40bnl1NDgifQ.ERemFCNO0wv2gwRjFoppqg', // Ganti dengan token yang valid
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

                        // Check Out Location (Kanan)
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
                                                'accessToken' => 'pk.eyJ1Ijoicml6a2kxIiwiYSI6ImNtYjZobTV2OTAwdGEycnNneW40bnl1NDgifQ.ERemFCNO0wv2gwRjFoppqg', 
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
        $isSuperAdmin = Auth::user()->hasRole('super_admin');

        $columns = [
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
                ->formatStateUsing(fn ($record) => $record->overdue_status_label)
                ->color(fn ($record) => $record->overdue_status_color),

            Tables\Columns\TextColumn::make('overdue_minutes')
                ->label('Keterlambatan (Menit)')
                ->formatStateUsing(fn ($state) => $state ? "{$state} menit" : '-'),

            Tables\Columns\BadgeColumn::make('return')
                ->label('Status Pulang')
                ->formatStateUsing(fn ($record) => $record->return_status_label)
                ->color(fn ($record) => $record->return_status_color),

            Tables\Columns\TextColumn::make('return_minutes')
                ->label('Pulang Awal (Menit)')
                ->formatStateUsing(fn ($state) => $state ? "{$state} menit" : '-'),

            Tables\Columns\BadgeColumn::make('overall_status')
                ->label('Status Keseluruhan')
                ->formatStateUsing(fn ($record) => $record->overall_status_label)
                ->color(fn ($record) => $record->overall_status_color),

            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('deleted_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        // Tambahkan kolom foto secara kondisional
        if ($isSuperAdmin) {
            // Untuk Super Admin, tampilkan kolom foto selalu
            $photoColumns = [
                 Tables\Columns\ImageColumn::make('start_attendance_photo')
                    ->label('Foto Datang')
                    ->circular()
                    ->placeholder(''),

                Tables\Columns\ImageColumn::make('end_attendance_photo')
                    ->label('Foto Pulang')
                    ->circular()
                    ->placeholder(''),
            ];
            $columns = array_merge($columns, $photoColumns);

        } else {
            // Untuk user non-Super Admin, tampilkan foto hanya jika ada DAN kamera user diaktifkan
             // Kita akan menggunakan TextColumn dan memformatnya untuk menampilkan Image jika kondisi terpenuhi
             // Ini untuk menghindari isu dengan ImageColumn dan visible/formatStateUsing yang mengakses relasi
            $photoColumns = [
                Tables\Columns\TextColumn::make('start_attendance_photo')
                    ->label('Foto Datang')
                    ->formatStateUsing(fn (?string $state, Attendance $record): string => 
                        $record->user !== null && !empty($state) && $record->user->is_camera_enabled 
                            ? '<img src="' . asset('storage/' . $state) . '" class="filament-tables-columns-image-column h-10 w-10 shrink-0 rounded-full object-cover" />' 
                            : '-'
                    )
                    ->html(),

                Tables\Columns\TextColumn::make('end_attendance_photo')
                    ->label('Foto Pulang')
                     ->formatStateUsing(fn (?string $state, Attendance $record): string => 
                        $record->user !== null && !empty($state) && $record->user->is_camera_enabled 
                            ? '<img src="' . asset('storage/' . $state) . '" class="filament-tables-columns-image-column h-10 w-10 shrink-0 rounded-full object-cover" />' 
                            : '-'
                    )
                     ->html(),
            ];
             // Sisipkan kolom foto setelah kolom created_at (atau sesuaikan posisinya sesuai keinginan)
             $columns = array_merge(
                 array_slice($columns, 0, 2),
                 $photoColumns,
                 array_slice($columns, 2)
             );
        }

        return $table->columns($columns)
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            // 'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['schedule.shift', 'user']);

        $user = Auth::user();
        $isSuperAdmin = $user?->hasRole('super_admin');

        // Jika user bukan super admin, filter data hanya untuk user yang sedang login
        if (!$isSuperAdmin) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

}
