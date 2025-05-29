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
use Humaidem\FilamentMapPicker\Fields\OSMMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Afsakar\LeafletMapPicker\LeafletMapPicker;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 1;

    use \App\Traits\HasNavigationBadge;

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        // Section kiri - Map dan koordinat
                        Forms\Components\Section::make('💡 Lokasi Kantor')
                            ->schema([
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Kantor')
                                            ->required()
                                            ->maxLength(255),
                                        
                                        Forms\Components\TextInput::make('radius')
                                            ->label('Radius Kantor')
                                            ->required()
                                            ->numeric()
                                            ->suffix('m')
                                            ->helperText('Radius coverage area in meters'),
                                    ])
                                    ->columns(2), // Membuat name dan radius bersebelahan

                                LeafletMapPicker::make('location')
                                    ->label('Lokasi Kantor')
                                    ->height('300px')
                                    ->required()
                                    ->dehydrated(false)
                                    ->defaultLocation([-7.257055247119025, 112.7564673120454]) 
                                    ->defaultZoom(12)
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
                                    }),
                            ])
                            ->columnSpan(2), // Section kiri mengambil 2/3 lebar

                        // Section kanan - Latitude dan Longitude
                        Forms\Components\Section::make('💡 Koordinat')
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->numeric()
                                    ->readOnly(),
                                Forms\Components\TextInput::make('longitude')
                                    ->numeric()
                                    ->readOnly(), 
                            ])
                            ->columnSpan(1), // Section kanan mengambil 1/3 lebar
                    ])
                    ->columns(3) // Total 3 kolom untuk layout side-by-side
                    ->columnSpanFull(), // Memastikan group mengambil full width
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kantor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->label('Latitude')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude')
                    ->label('Longitude')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('radius')
                    ->label('Radius')
                    ->numeric()
                    ->suffix(' m')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }
}