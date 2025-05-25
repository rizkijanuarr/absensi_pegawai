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

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 5;

    use \App\Traits\HasNavigationBadge;

    protected static ?string $navigationGroup = 'Presensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Section 1: Informasi Pegawai
                Forms\Components\Section::make('Informasi Pegawai')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->label('Nama Pegawai')
                            ->disabled()
                            ->formatStateUsing(fn ($record) => $record?->user?->name ?? $record?->user_id),
                        
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('schedule_start_time')
                                    ->label('Waktu mulai bekerja')
                                    ->disabled(),
                                Forms\Components\TextInput::make('schedule_end_time')
                                    ->label('Waktu akhir bekerja')
                                    ->disabled(),
                                Forms\Components\TextInput::make('start_time')
                                    ->label('Waktu jam datang')
                                    ->disabled(),
                                Forms\Components\TextInput::make('end_time')
                                    ->label('Waktu jam pulang')
                                    ->disabled(),
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
                                OSMMap::make('start_location')
                                    ->label('Check-in Location')
                                    ->showMarker()
                                    ->draggable(false)
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Forms\Set $set, $state, $record) {
                                        if ($record && $record->start_latitude && $record->start_longitude) {
                                            $set('start_location', [
                                                'lat' => $record->start_latitude,
                                                'lng' => $record->start_longitude,
                                            ]);
                                        }
                                    })
                                    ->tilesUrl('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('start_latitude')
                                            ->label('Checkin Latitude')
                                            ->disabled()
                                            ->numeric(),
                                        Forms\Components\TextInput::make('start_longitude')
                                            ->label('Checkin Longitude')
                                            ->disabled()
                                            ->numeric(),
                                    ])
                                    ->columns(2),
                            ])
                            ->columnSpan(1),

                        // Check Out Location (Kanan)
                        Forms\Components\Section::make('Check Out Location')
                            ->schema([
                                OSMMap::make('end_location')
                                    ->label('Check-out Location')
                                    ->showMarker()
                                    ->draggable(false)
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Forms\Set $set, $state, $record) {
                                        if ($record && $record->end_latitude && $record->end_longitude) {
                                            $set('end_location', [
                                                'lat' => $record->end_latitude,
                                                'lng' => $record->end_longitude,
                                            ]);
                                        }
                                    })
                                    ->tilesUrl('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')
                                    ->visible(fn ($record) => $record && $record->end_latitude && $record->end_longitude),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('end_latitude')
                                            ->label('Checkout Latitude')
                                            ->disabled()
                                            ->numeric(),
                                        Forms\Components\TextInput::make('end_longitude')
                                            ->label('Checkout Longitude')
                                            ->disabled()
                                            ->numeric(),
                                    ])
                                    ->columns(2)
                                    ->visible(fn ($record) => $record && ($record->end_latitude || $record->end_longitude)),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                // Section 3: Office Location
                Forms\Components\Section::make('Office Location')
                    ->schema([
                        OSMMap::make('schedule_location')
                            ->label('Office Location')
                            ->showMarker()
                            ->draggable(false)
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Forms\Set $set, $state, $record) {
                                if ($record && $record->schedule_latitude && $record->schedule_longitude) {
                                    $set('schedule_location', [
                                        'lat' => $record->schedule_latitude,
                                        'lng' => $record->schedule_longitude,
                                    ]);
                                }
                            })
                            ->tilesUrl('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('schedule_latitude')
                                    ->label('Office Latitude')
                                    ->disabled()
                                    ->numeric(),
                                Forms\Components\TextInput::make('schedule_longitude')
                                    ->label('Office Longitude')
                                    ->disabled()
                                    ->numeric(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
         ->modifyQueryUsing(function (Builder $query) {
                $is_super_admin = Auth::user()->hasRole('super_admin');

                if (!$is_super_admin) {
                    $query->where('user_id', Auth::user()->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pegawai')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),

                Tables\Columns\ImageColumn::make('start_attendance_photo')
                    ->label('Foto Datang')
                    ->circular()
                    ->placeholder(''),

                Tables\Columns\ImageColumn::make('end_attendance_photo')
                    ->label('Foto Pulang')
                    ->circular()
                    ->placeholder(''),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Waktu Datang')
                    ->formatStateUsing(fn ($state) => $state ?? ''),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Waktu Pulang')
                    ->formatStateUsing(fn ($state) => $state ?? ''),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['schedule.shift']);
    }

}
