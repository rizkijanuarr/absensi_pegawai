<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Shift;
use App\Models\Office;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Get the first shift and office
        $shift = Shift::first();
        $office = Office::first();

        // Delete existing schedules first
        Schedule::truncate();

        // Create schedule for each user (excluding super admin)
        User::where('id', '>', 1)->each(function ($user) use ($shift, $office) {
            Schedule::create([
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'office_id' => $office->id,
                'is_wfa' => true,
            ]);
        });
    }
} 