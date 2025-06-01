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
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;

class ShiftResource extends Resource
{
    private const LOG_CHANNEL = 'shift_management';

    protected static ?string $model = Shift::class;
    protected static ?string $navigationIcon = 'heroicon-c-numbered-list';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Master Data';

    use \App\Traits\HasNavigationBadge;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        Section::make('💡 Shift')
                            ->schema([
                                self::getNameField(),
                                self::getTimeFields(),
                            ]),
                    ])
            ]);
    }

    private static function getNameField(): TextInput
    {
        return TextInput::make('name')
            ->label('Nama Shift')
            ->live(onBlur: true)
            ->required()
            ->maxLength(255);
    }

    private static function getTimeFields(): Grid
    {
        return Grid::make(2)
            ->schema([
                TimePicker::make('start_time')
                    ->label('Shift Mulai Bekerja')
                    ->displayFormat('H:i')
                    ->native(false)
                    ->seconds(false)
                    ->required(),
                TimePicker::make('end_time')
                    ->label('Shift Akhir Bekerja')
                    ->displayFormat('H:i')
                    ->native(false)
                    ->seconds(false)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                self::getNameColumn(),
                self::getStartTimeColumn(),
                self::getEndTimeColumn(),
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
            ->label('Nama Shift')
            ->searchable();
    }

    private static function getStartTimeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('start_time')
            ->label('Shift Mulai Bekerja')
            ->suffix(' WIB');
    }

    private static function getEndTimeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('end_time')
            ->label('Shift Akhir Bekerja')
            ->suffix(' WIB');
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
            ->before(function (Shift $record) {
                Log::channel(self::LOG_CHANNEL)->info('Shift viewed', [
                    'shift_id' => $record->id,
                    'name' => $record->name,
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
            ->before(function (Shift $record) {
                Log::channel(self::LOG_CHANNEL)->info('Shift edit started', [
                    'shift_id' => $record->id,
                    'name' => $record->name,
                    'start_time' => $record->start_time,
                    'end_time' => $record->end_time
                ]);
            })
            ->after(function (Shift $record) {
                Log::channel(self::LOG_CHANNEL)->info('Shift updated', [
                    'shift_id' => $record->id,
                    'name' => $record->name,
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
            ->before(function (Shift $record) {
                Log::channel(self::LOG_CHANNEL)->info('Shift deletion started', [
                    'shift_id' => $record->id,
                    'name' => $record->name,
                    'start_time' => $record->start_time,
                    'end_time' => $record->end_time
                ]);
            })
            ->after(function (Shift $record) {
                Log::channel(self::LOG_CHANNEL)->info('Shift deleted', [
                    'shift_id' => $record->id,
                    'name' => $record->name,
                    'start_time' => $record->start_time,
                    'end_time' => $record->end_time
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
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
