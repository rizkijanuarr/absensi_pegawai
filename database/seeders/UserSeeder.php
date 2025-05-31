<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Delete existing users except super admin
        User::where('id', '>', 1)->delete();

        // Create first Karyawan user
        $karyawan1 = User::create([
            'name' => 'Rizki',
            'email' => 'rizki@gmail.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_camera_enabled' => false,
            'image' => null,
        ]);

        // Create second Karyawan user
        $karyawan2 = User::create([
            'name' => 'Fefe',
            'email' => 'fefe@gmail.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_camera_enabled' => false,
            'image' => null,
        ]);

        // Assign role to both Karyawan
        $karyawan1->assignRole('karyawan');
        $karyawan2->assignRole('karyawan');
    }
}