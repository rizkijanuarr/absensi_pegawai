<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        Shift::create([
            'name' => 'SHIFT PPNPN SENIN - KAMIS',
            'start_time' => '07:30:00',
            'end_time' => '16:00:00',
        ]);
    }
} 