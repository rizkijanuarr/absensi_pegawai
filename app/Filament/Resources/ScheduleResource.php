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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Group;

class ScheduleResource extends Resource
{
    private const LOG_CHANNEL = 'schedule_management';

    protected static ?string $model = Schedule::class;
    protected static ?string $navigationIcon = 'heroicon-s-calendar-days';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationGroup = 'Manajamen Presensi';

    use \App\Traits\HasNavigationBadge;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        Section::make('💡 Schedule Pegawai')
                            ->schema([
                                self::getUserField(),
                                self::getScheduleFields(),
                                self::getWfaToggleField(),
                            ])->columnSpanFull(),
                    ]),
            ]);
    }

    private static function getUserField(): Select
    {
        return Select::make('user_id')
            ->label('Nama Pegawai')
            ->relationship(
                'user', 'name',
                fn ($query) => $query->latest()
            )
            ->required()
            ->preload()
            ->searchable();
    }

    private static function getScheduleFields(): Group
    {
        return Group::make()
            ->schema([
                Select::make('shift_id')
                    ->label('Shift')
                    ->relationship('shift', 'name')
                    ->required(),
                Select::make('office_id')
                    ->label('Kantor')
                    ->relationship('office', 'name')
                    ->required(),
            ])->columns(2);
    }

    private static function getWfaToggleField(): Toggle
    {
        return Toggle::make('is_wfa')
            ->label('Diperbolehkan Absensi Diluar Radius Kantor?');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(self::getQueryModifier())
            ->columns([
                self::getUserNameColumn(),
                self::getUserEmailColumn(),
                self::getWfaColumn(),
                self::getShiftColumn(),
                self::getOfficeColumn(),
                self::getCreatedAtColumn(),
                self::getUpdatedAtColumn(),
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

    private static function getQueryModifier(): \Closure
    {
        return function (Builder $query) {
            $is_super_admin = Auth::user()->hasRole('super_admin');

            if (!$is_super_admin) {
                $query->where('user_id', Auth::user()->id);
            }

            Log::channel(self::LOG_CHANNEL)->info('Schedule query modified', [
                'user_id' => Auth::id(),
                'is_super_admin' => $is_super_admin
            ]);
        };
    }

    private static function getUserNameColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('user.name')
            ->searchable()
            ->label('Name')
            ->sortable();
    }

    private static function getUserEmailColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('user.email')
            ->searchable()
            ->label('Email')
            ->sortable();
    }

    private static function getWfaColumn(): Tables\Columns\BooleanColumn
    {
        return Tables\Columns\BooleanColumn::make('is_wfa')
            ->label('WFA');
    }

    private static function getShiftColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('shift.name')
            ->description(fn (Schedule $record): string => $record->shift->start_time.'-'.$record->shift->end_time)
            ->sortable();
    }

    private static function getOfficeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('office.name')
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

    private static function getViewAction(): Tables\Actions\ViewAction
    {
        return Tables\Actions\ViewAction::make()
            ->color('gray')
            ->button()
            ->icon('heroicon-o-eye')
            ->before(function (Schedule $record) {
                Log::channel(self::LOG_CHANNEL)->info('Schedule viewed', [
                    'schedule_id' => $record->id,
                    'user_id' => $record->user_id,
                    'shift_id' => $record->shift_id,
                    'office_id' => $record->office_id,
                    'is_wfa' => $record->is_wfa
                ]);
            });
    }

    private static function getEditAction(): Tables\Actions\EditAction
    {
        return Tables\Actions\EditAction::make()
            ->color('primary')
            ->button()
            ->icon('heroicon-o-pencil-square')
            ->before(function (Schedule $record) {
                Log::channel(self::LOG_CHANNEL)->info('Schedule edit started', [
                    'schedule_id' => $record->id,
                    'user_id' => $record->user_id,
                    'shift_id' => $record->shift_id,
                    'office_id' => $record->office_id,
                    'is_wfa' => $record->is_wfa
                ]);
            })
            ->after(function (Schedule $record) {
                Log::channel(self::LOG_CHANNEL)->info('Schedule updated', [
                    'schedule_id' => $record->id,
                    'user_id' => $record->user_id,
                    'shift_id' => $record->shift_id,
                    'office_id' => $record->office_id,
                    'is_wfa' => $record->is_wfa
                ]);
            });
    }

    private static function getDeleteAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->color('danger')
            ->button()
            ->icon('heroicon-o-trash')
            ->before(function (Schedule $record) {
                Log::channel(self::LOG_CHANNEL)->info('Schedule deletion started', [
                    'schedule_id' => $record->id,
                    'user_id' => $record->user_id,
                    'shift_id' => $record->shift_id,
                    'office_id' => $record->office_id,
                    'is_wfa' => $record->is_wfa
                ]);
            })
            ->after(function (Schedule $record) {
                Log::channel(self::LOG_CHANNEL)->info('Schedule deleted', [
                    'schedule_id' => $record->id,
                    'user_id' => $record->user_id,
                    'shift_id' => $record->shift_id,
                    'office_id' => $record->office_id,
                    'is_wfa' => $record->is_wfa
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
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
