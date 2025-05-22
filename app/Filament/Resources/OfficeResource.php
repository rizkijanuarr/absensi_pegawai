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

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        // Section kiri - Map dan koordinat
                        Forms\Components\Section::make('Location')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),

                                Map::make('location')
                                    ->mapControls([
                                        'mapTypeControl'    => true,
                                        'scaleControl'      => true,
                                        'streetViewControl' => true,
                                        'rotateControl'     => true,
                                        'fullscreenControl' => true,
                                        'searchBoxControl'  => false,
                                        'zoomControl'       => false,
                                    ])
                                    ->height(fn () => '400px')
                                    ->defaultZoom(15)
                                    ->autocomplete('full_address')
                                    ->autocompleteReverse(true)
                                    ->reverseGeocode([
                                        'street_number'   => '%n',
                                        'route'           => '%S',
                                        'locality'        => '%L',
                                        'administrative_area_level_1' => '%A1',
                                        'country'         => '%C',
                                        'postal_code'     => '%z',
                                    ])
                                    ->debug()
                                    ->defaultLocation([0, 0])
                                    ->draggable()
                                    ->clickable(true)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state && is_array($state)) {
                                            if (isset($state['lat'])) {
                                                $set('latitude', $state['lat']);
                                            }
                                            if (isset($state['lng'])) {
                                                $set('longitude', $state['lng']);
                                            }
                                        }
                                    })
                                    ->afterStateHydrated(function (Forms\Set $set, $state, $record) {
                                        if ($record && $record->latitude && $record->longitude) {
                                            $set('location', [
                                                'lat' => $record->latitude,
                                                'lng' => $record->longitude,
                                            ]);
                                        }
                                    }),

                                // Latitude dan Longitude bersebelahan
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('latitude')
                                            ->required()
                                            ->numeric()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                                $lng = $get('longitude');
                                                if ($state !== null && $lng !== null) {
                                                    $set('location', ['lat' => $state, 'lng' => $lng]);
                                                }
                                            })
                                            ->disabled(),

                                        Forms\Components\TextInput::make('longitude')
                                            ->required()
                                            ->numeric()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                                $lat = $get('latitude');
                                                if ($state !== null && $lat !== null) {
                                                    $set('location', ['lat' => $lat, 'lng' => $state]);
                                                }
                                            })
                                            ->disabled(),
                                    ])
                                    ->columns(2), // Membuat latitude dan longitude bersebelahan
                            ])
                            ->columnSpan(2), // Section kiri mengambil 2/3 lebar

                        // Section kanan - Radius
                        Forms\Components\Section::make('Settings')
                            ->schema([
                                Forms\Components\TextInput::make('radius')
                                    ->required()
                                    ->numeric()
                                    ->suffix('km')
                                    ->helperText('Radius coverage area in kilometers'),
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
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('radius')
                    ->numeric()
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
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }
}
