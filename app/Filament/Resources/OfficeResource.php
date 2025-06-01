<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeResource\Pages;
use App\Filament\Resources\OfficeResource\RelationManagers;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Afsakar\LeafletMapPicker\LeafletMapPicker;

class OfficeResource extends Resource
{
    private const LOG_CHANNEL = 'office_management';
    private const DEFAULT_LOCATION = [-7.257055247119025, 112.7564673120454];
    private const DEFAULT_ZOOM = 12;
    private const MAP_HEIGHT = '300px';

    protected static ?string $model = Office::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Master Data';

    use \App\Traits\HasNavigationBadge;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        self::getLocationSection(),
                        self::getCoordinatesSection(),
                    ])->columns(3)->columnSpanFull(),
            ]);
    }

    private static function getLocationSection(): Section
    {
        return Section::make('💡 Lokasi Kantor')
            ->schema([
                self::getOfficeFields(),
                self::getMapField(),
            ])
            ->columnSpan(2);
    }

    private static function getOfficeFields(): Group
    {
        return Group::make()
            ->schema([
                TextInput::make('name')
                    ->label('Nama Kantor')
                    ->required()
                    ->maxLength(255),
                TextInput::make('radius')
                    ->label('Radius Kantor')
                    ->required()
                    ->numeric()
                    ->suffix('m')
                    ->helperText('Radius coverage area in meters'),
            ])
            ->columns(2);
    }

    private static function getMapField(): LeafletMapPicker
    {
        return LeafletMapPicker::make('location')
            ->label('Lokasi Kantor')
            ->height(self::MAP_HEIGHT)
            ->required()
            ->dehydrated(false)
            ->defaultLocation(self::DEFAULT_LOCATION)
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
                    $set('location', [
                        'lat' => $record->latitude,
                        'lng' => $record->longitude,
                    ]);
                }
            })
            ->afterStateUpdated(function ($state, Forms\Set $set) {
                if (is_array($state)) {
                    $set('latitude', $state['lat']);
                    $set('longitude', $state['lng']);
                }
            });
    }

    private static function getCoordinatesSection(): Section
    {
        return Section::make('💡 Koordinat')
            ->schema([
                TextInput::make('latitude')
                    ->numeric()
                    ->readOnly(),
                TextInput::make('longitude')
                    ->numeric()
                    ->readOnly(),
            ])
            ->columnSpan(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                self::getNameColumn(),
                self::getLatitudeColumn(),
                self::getLongitudeColumn(),
                self::getRadiusColumn(),
                self::getCreatedAtColumn(),
                self::getUpdatedAtColumn(),
                self::getDeletedAtColumn(),
            ])
            ->filters([])
            ->actions([
                self::getViewAction(),
                self::getEditAction(),
                self::getDeleteAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function getNameColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('name')
            ->label('Nama Kantor')
            ->searchable();
    }

    private static function getLatitudeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('latitude')
            ->label('Latitude')
            ->numeric()
            ->sortable();
    }

    private static function getLongitudeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('longitude')
            ->label('Longitude')
            ->numeric()
            ->sortable();
    }

    private static function getRadiusColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('radius')
            ->label('Radius')
            ->numeric()
            ->suffix(' m')
            ->sortable();
    }

    private static function getCreatedAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    private static function getUpdatedAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('updated_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    private static function getDeletedAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('deleted_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    private static function getViewAction(): Tables\Actions\ViewAction
    {
        return Tables\Actions\ViewAction::make()
            ->color('gray')
            ->button()
            ->icon('heroicon-o-eye')
            ->before(function (Office $record) {
                Log::channel(self::LOG_CHANNEL)->info('Office viewed', [
                    'office_id' => $record->id,
                    'name' => $record->name,
                    'latitude' => $record->latitude,
                    'longitude' => $record->longitude,
                    'radius' => $record->radius
                ]);
            });
    }

    private static function getEditAction(): Tables\Actions\EditAction
    {
        return Tables\Actions\EditAction::make()
            ->color('primary')
            ->button()
            ->icon('heroicon-o-pencil-square')
            ->before(function (Office $record) {
                Log::channel(self::LOG_CHANNEL)->info('Office edit started', [
                    'office_id' => $record->id,
                    'name' => $record->name,
                    'latitude' => $record->latitude,
                    'longitude' => $record->longitude,
                    'radius' => $record->radius
                ]);
            })
            ->after(function (Office $record) {
                Log::channel(self::LOG_CHANNEL)->info('Office updated', [
                    'office_id' => $record->id,
                    'name' => $record->name,
                    'latitude' => $record->latitude,
                    'longitude' => $record->longitude,
                    'radius' => $record->radius
                ]);
            });
    }

    private static function getDeleteAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->color('danger')
            ->button()
            ->icon('heroicon-o-trash')
            ->before(function (Office $record) {
                Log::channel(self::LOG_CHANNEL)->info('Office deletion started', [
                    'office_id' => $record->id,
                    'name' => $record->name,
                    'latitude' => $record->latitude,
                    'longitude' => $record->longitude,
                    'radius' => $record->radius
                ]);
            })
            ->after(function (Office $record) {
                Log::channel(self::LOG_CHANNEL)->info('Office deleted', [
                    'office_id' => $record->id,
                    'name' => $record->name,
                    'latitude' => $record->latitude,
                    'longitude' => $record->longitude,
                    'radius' => $record->radius
                ]);
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }
}