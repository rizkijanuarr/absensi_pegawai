<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Filament\Resources\ScheduleResource\RelationManagers;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Auth;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-s-calendar-days';

    protected static ?int $navigationSort = 4;

    use \App\Traits\HasNavigationBadge;

    protected static ?string $navigationGroup = 'Manajamen Presensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(12) 
                    ->schema([
                        Forms\Components\Section::make('💡 Schedule Pegawai')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Nama Pegawai')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('shift_id')
                                    ->label('Shift')
                                    ->relationship('shift', 'name')
                                    ->required(),
                                Forms\Components\Select::make('office_id')
                                    ->label('Kantor')
                                    ->relationship('office', 'name')
                                    ->required(),
                                Forms\Components\Toggle::make('is_wfa')
                                    ->label('Diperbolehkan Absensi Diluar Radius Kantor?')
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $is_super_admin = Auth::user()->hasRole('super_admin');

                if (!$is_super_admin) {
                    $query->where('user_id', Auth::user()->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->label('Name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->searchable()
                    ->label('Email')
                    ->sortable(),
                Tables\Columns\BooleanColumn::make('is_wfa')
                    ->label('WFA'),
                Tables\Columns\TextColumn::make('shift.name')
                    ->description(fn (Schedule $record): string => $record->shift->start_time.'-'.$record->shift->end_time)
                    ->sortable(),
                Tables\Columns\TextColumn::make('office.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
