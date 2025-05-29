<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Filament\Resources\ShiftResource\RelationManagers;
use App\Models\Shift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use HusamTariq\FilamentTimePicker\Forms\Components\TimePickerField;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-c-numbered-list';

    protected static ?int $navigationSort = 2;

    use \App\Traits\HasNavigationBadge;

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12)
                ->schema([
                    Forms\Components\Section::make('Shift')
                        ->description('💡 Informasi Waktu AM 00.00 - 11.59 | Waktu PM 12.00 - 23.59')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nama Shift')
                                ->live(onBlur: true)
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    TimePickerField::make('start_time')
                                        ->label('Shift Mulai Bekerja')
                                        ->okLabel('Konfirmasi')
                                        ->cancelLabel('Batal')
                                        ->required(),
                                    TimePickerField::make('end_time')
                                        ->label('Shift Akhir Bekerja')
                                        ->okLabel('Konfirmasi')
                                        ->cancelLabel('Batal')
                                        ->required(),
                                ]),
                        ]),
                ])
            ]);
    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Shift')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Shift Mulai Bekerja')
                    ->suffix(' WIB'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Shift Akhir Bekerja')
                    ->suffix(' WIB'),
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
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
