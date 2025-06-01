<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;

class UserResource extends Resource
{
    private const LOG_CHANNEL = 'user_management';
    private const PHOTO_DIRECTORY = 'photos';

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-s-user-group';

    protected static ?int $navigationSort = 3;

    use \App\Traits\HasNavigationBadge;

    protected static ?string $navigationGroup = 'Manajamen User';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('💡 Data Pegawai')
                ->schema([
                    self::getPhotoUploadField(),
                    Grid::make(2)
                        ->schema([
                            self::getNameField(),
                            self::getEmailField(),
                            self::getPasswordFields(),
                            self::getRolesField(),
                            self::getCameraToggleField(),
                        ]),  
                ])->columns(2),
        ]);
    }

    private static function getPhotoUploadField(): FileUpload
    {
        return FileUpload::make('image')
            ->image()
            ->directory(self::PHOTO_DIRECTORY)
            ->visibility('public')
            ->label('Profil Pegawai')
            ->required()
            ->columnSpanFull();
    }

    private static function getNameField(): TextInput
    {
        return TextInput::make('name')
            ->required()
            ->label('Nama Pegawai')
            ->maxLength(255);
    }

    private static function getEmailField(): TextInput
    {
        return TextInput::make('email')
            ->email()
            ->required()
            ->label('Email Pegawai')
            ->maxLength(255);
    }

    private static function getPasswordFields(): Grid
    {
        return Grid::make(2)
            ->schema([
                TextInput::make('password')
                    ->password()
                    ->label('Password')
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->live(1000)
                    ->revealable(),

                TextInput::make('passwordConfirmation')
                    ->password()
                    ->label('Konfirmasi Password')
                    ->dehydrated(false)
                    ->required()
                    ->revealable()
                    ->hidden(fn (Forms\Get $get) => $get('password') == null),
            ])->columnSpanFull();
    }

    private static function getRolesField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('roles')
            ->relationship('roles', 'name')
            ->label('Hak Akses')
            ->required()
            ->preload()
            ->searchable()
            ->columnSpanFull();
    }

    private static function getCameraToggleField(): Toggle
    {
        return Toggle::make('is_camera_enabled')
            ->label('Aktifkan Kamera untuk Presensi ?')
            ->required()
            ->columnSpanFull();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                self::getProfileColumn(),
                self::getNameColumn(),
                self::getEmailColumn(),
                self::getRolesColumn(),
                self::getCameraColumn(),
                self::getCreatedAtColumn(),
                self::getUpdatedAtColumn(),
                self::getDeletedAtColumn(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                self::getViewAction(),
                self::getEditAction(),
                self::getDeleteAction(),    
                self::getRestoreAction(),
                self::getForceDeleteAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function getProfileColumn(): Tables\Columns\ImageColumn
    {
        return Tables\Columns\ImageColumn::make('image')
            ->label('Profil')
            ->circular();
    }

    private static function getNameColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('name')
            ->label('Nama')
            ->searchable();
    }

    private static function getEmailColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('email')
            ->label('Email')
            ->searchable();
    }

    private static function getRolesColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('roles.name')
            ->label('Hak Akses')
            ->badge()
            ->searchable();
    }

    private static function getCameraColumn(): Tables\Columns\BooleanColumn
    {
        return Tables\Columns\BooleanColumn::make('is_camera_enabled')
            ->label('Kamera Aktif ?');
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
            ->before(function (User $record) {
                Log::channel(self::LOG_CHANNEL)->info('User viewed', [
                    'user_id' => $record->id,
                    'name' => $record->name,
                    'email' => $record->email
                ]);
            });
    }

    private static function getEditAction(): Tables\Actions\EditAction
    {
        return Tables\Actions\EditAction::make()
            ->color('primary')
            ->button()
            ->icon('heroicon-o-pencil-square')
            ->before(function (User $record) {
                Log::channel(self::LOG_CHANNEL)->info('User edit started', [
                    'user_id' => $record->id,
                    'name' => $record->name,
                    'email' => $record->email
                ]);
            })
            ->after(function (User $record) {
                Log::channel(self::LOG_CHANNEL)->info('User updated', [
                    'user_id' => $record->id,
                    'name' => $record->name,
                    'email' => $record->email,
                    'roles' => $record->roles->pluck('name'),
                    'is_camera_enabled' => $record->is_camera_enabled
                ]);
            });
    }

    private static function getDeleteAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->color('danger')
            ->button()
            ->icon('heroicon-o-trash')
            ->before(function (User $record) {
                Log::channel(self::LOG_CHANNEL)->info('User soft delete started', [
                    'user_id' => $record->id,
                    'name' => $record->name,
                    'email' => $record->email
                ]);
            })
            ->after(function (User $record) {
                Log::channel(self::LOG_CHANNEL)->info('User soft deleted', [
                    'user_id' => $record->id,
                    'name' => $record->name,
                    'email' => $record->email
                ]);
            });
    }

    private static function getRestoreAction(): Tables\Actions\RestoreAction
    {
        return Tables\Actions\RestoreAction::make()
            ->color('success')
            ->button()
            ->icon('heroicon-o-arrow-uturn-left')
            ->before(function (User $record) {
                Log::channel(self::LOG_CHANNEL)->info('User restore started', [
                    'user_id' => $record->id,
                    'name' => $record->name,
                    'email' => $record->email
                ]);
            })
            ->after(function (User $record) {
                Log::channel(self::LOG_CHANNEL)->info('User restored', [
                    'user_id' => $record->id,
                    'name' => $record->name,
                    'email' => $record->email
                ]);
            });
    }

    private static function getForceDeleteAction(): Tables\Actions\ForceDeleteAction
    {
        return Tables\Actions\ForceDeleteAction::make()
            ->color('danger')
            ->button()
            ->icon('heroicon-o-trash')
            ->before(function (User $record) {
                Log::channel(self::LOG_CHANNEL)->info('User force delete started', [
                    'user_id' => $record->id,
                    'name' => $record->name,
                    'email' => $record->email
                ]);
            })
            ->after(function (User $record) {
                Log::channel(self::LOG_CHANNEL)->info('User force deleted', [
                    'user_id' => $record->id,
                    'name' => $record->name,
                    'email' => $record->email
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
