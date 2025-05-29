<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms;
use Spatie\Permission\Models\Role;

class Register extends BaseRegister
{
    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    {
        $photoPath = null;
        if (!empty($data['image'])) {
            $photoPath = $data['image'];
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'image' => $photoPath,
        ]);

        // PERBAIKAN: Pastikan menggunakan nama role yang konsisten
        // Cek dulu role mana yang ada: 'Karyawan' atau 'karyawan'
        $karyawanRole = Role::where('name', 'Karyawan')->first() 
                       ?? Role::where('name', 'karyawan')->first();
        
        if ($karyawanRole) {
            $user->assignRole($karyawanRole);
        }

        return $user;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('image')
                    ->label('Foto')
                    ->image()
                    ->directory('photos')
                    ->visibility('public')
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('200')
                    ->imageResizeTargetHeight('200'),
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->same('passwordConfirmation'),
                Forms\Components\TextInput::make('passwordConfirmation')
                    ->label('Konfirmasi Password')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->dehydrated(false),
            ]);
    }
}