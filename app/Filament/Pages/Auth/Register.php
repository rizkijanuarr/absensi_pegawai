<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms;
use Spatie\Permission\Models\Role;

class Register extends BaseRegister
{
    private const LOG_CHANNEL = 'register';
    private const PHOTO_DIRECTORY = 'photos';
    private const PHOTO_DIMENSIONS = [
        'width' => 200,
        'height' => 200,
        'aspect_ratio' => '1:1'
    ];

    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            Log::channel(self::LOG_CHANNEL)->info('Starting user registration process', [
                'email' => $data['email'],
                'name' => $data['name']
            ]);

            $user = $this->createUser($data);
            $this->assignDefaultRole($user);

            Log::channel(self::LOG_CHANNEL)->info('User registration completed successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return $user;
        } catch (\Exception $e) {
            Log::channel(self::LOG_CHANNEL)->error('User registration failed', [
                'error' => $e->getMessage(),
                'email' => $data['email'] ?? null
            ]);
            throw $e;
        }
    }

    private function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'image' => $data['image'] ?? null,
        ]);
    }

    private function assignDefaultRole(User $user): void
    {
        $karyawanRole = Role::where('name', 'Karyawan')->first() 
                       ?? Role::where('name', 'karyawan')->first();
        
        if ($karyawanRole) {
            $user->assignRole($karyawanRole);
            Log::channel(self::LOG_CHANNEL)->info('Default role assigned to user', [
                'user_id' => $user->id,
                'role' => $karyawanRole->name
            ]);
        } else {
            Log::channel(self::LOG_CHANNEL)->warning('Default role not found for user', [
                'user_id' => $user->id
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getPhotoUploadField(),
                $this->getNameField(),
                $this->getEmailField(),
                $this->getPasswordField(),
                $this->getPasswordConfirmationField(),
            ]);
    }

    private function getPhotoUploadField(): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make('image')
            ->label('Foto')
            ->image()
            ->directory(self::PHOTO_DIRECTORY)
            ->visibility('public')
            ->imageResizeMode('cover')
            ->imageCropAspectRatio(self::PHOTO_DIMENSIONS['aspect_ratio'])
            ->imageResizeTargetWidth(self::PHOTO_DIMENSIONS['width'])
            ->imageResizeTargetHeight(self::PHOTO_DIMENSIONS['height']);
    }

    private function getNameField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('name')
            ->label('Nama')
            ->required()
            ->maxLength(255);
    }

    private function getEmailField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->maxLength(255);
    }

    private function getPasswordField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('password')
            ->label('Password')
            ->password()
            ->required()
            ->minLength(8)
            ->same('passwordConfirmation');
    }

    private function getPasswordConfirmationField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('passwordConfirmation')
            ->label('Konfirmasi Password')
            ->password()
            ->required()
            ->minLength(8)
            ->dehydrated(false);
    }
}