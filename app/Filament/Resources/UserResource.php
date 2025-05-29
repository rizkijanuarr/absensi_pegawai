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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-s-user-group';

    protected static ?int $navigationSort = 3;

    use \App\Traits\HasNavigationBadge;

    protected static ?string $navigationGroup = 'Manajamen User';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('💡 Data Pegawai')
                ->schema([
                    Forms\Components\FileUpload::make('image')
                        ->image()
                        ->directory('photos')
                        ->visibility('public')
                        ->label('Profil Pegawai')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->label('Nama Pegawai')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->required()
                                ->label('Email Pegawai')
                                ->maxLength(255),
                            
                                Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('password')
                                        ->password()
                                        ->label('Password')
                                        ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                                        ->dehydrated(fn ($state) => filled($state))
                                        ->required(fn (string $context): bool => $context === 'create')
                                        ->live(1000)
                                        ->revealable(),

                                    Forms\Components\TextInput::make('passwordConfirmation')
                                        ->password()
                                        ->label('Konfirmasi Password')
                                        ->dehydrated(false)
                                        ->required()
                                        ->revealable()
                                        ->hidden(fn (Forms\Get $get) => $get('password') == null),
                                    ])->columnSpanFull(),

                                Forms\Components\Select::make('roles')
                                        ->relationship('roles', 'name')
                                        ->label('Hak Akses')
                                        ->required()
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->columnSpanFull(),
                            
                                Forms\Components\Toggle::make('is_camera_enabled')
                                    ->label('Aktifkan Kamera untuk Presensi ?')
                                    ->required()
                                    ->columnSpanFull(),
                        ]),  
                ])->columns(2),
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Profil')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Hak Akses')
                    ->badge()
                    ->searchable(),
                Tables\Columns\BooleanColumn::make('is_camera_enabled')
                    ->label('Kamera Aktif'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
