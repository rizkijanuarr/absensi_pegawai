<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Office;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        Office::create([
            'name' => 'temanweb.id',
            'latitude' => -7.358352,
            'longitude' => 112.671585,
            'radius' => 100,
        ]);
    }
} 