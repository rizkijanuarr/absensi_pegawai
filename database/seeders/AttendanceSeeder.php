<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Schedule;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Get all users except super admin
        $users = User::where('id', '>', 1)->get();

        foreach ($users as $user) {
            $schedule = Schedule::where('user_id', $user->id)->first();
            
            if (!$schedule) continue;

            // Create 7 different attendance records for each user
            $dates = collect(range(1, 7))->map(function ($day) {
                return Carbon::now()->subDays($day);
            });

            foreach ($dates as $index => $date) {
                $startTime = Carbon::parse($schedule->shift->start_time);
                $endTime = Carbon::parse($schedule->shift->end_time);

                // Different scenarios for each day
                switch ($index) {
                    case 0: // On time both
                        $actualStart = $startTime->copy()->subMinutes(5);
                        $actualEnd = $endTime->copy()->addMinutes(5);
                        $overdue = 'on_time';
                        $return = 'on_time';
                        $overdueMinutes = 0;
                        $returnMinutes = 0;
                        break;
                    case 1: // Late arrival, early leave
                        $actualStart = $startTime->copy()->addMinutes(30);
                        $actualEnd = $endTime->copy()->subMinutes(30);
                        $overdue = 'tl_1';
                        $return = 'psw_1';
                        $overdueMinutes = 30;
                        $returnMinutes = 30;
                        break;
                    case 2: // Very late arrival, very early leave
                        $actualStart = $startTime->copy()->addMinutes(90);
                        $actualEnd = $endTime->copy()->subMinutes(90);
                        $overdue = 'tl_2';
                        $return = 'psw_2';
                        $overdueMinutes = 90;
                        $returnMinutes = 90;
                        break;
                    case 3: // Not present
                        $actualStart = null;
                        $actualEnd = null;
                        $overdue = 'not_present';
                        $return = 'not_present';
                        $overdueMinutes = 0;
                        $returnMinutes = 0;
                        break;
                    case 4: // Late arrival, on time leave
                        $actualStart = $startTime->copy()->addMinutes(45);
                        $actualEnd = $endTime->copy()->addMinutes(5);
                        $overdue = 'tl_1';
                        $return = 'on_time';
                        $overdueMinutes = 45;
                        $returnMinutes = 0;
                        break;
                    case 5: // On time arrival, early leave
                        $actualStart = $startTime->copy()->subMinutes(5);
                        $actualEnd = $endTime->copy()->subMinutes(45);
                        $overdue = 'on_time';
                        $return = 'psw_2';
                        $overdueMinutes = 0;
                        $returnMinutes = 45;
                        break;
                    case 6: // Late arrival, late leave
                        $actualStart = $startTime->copy()->addMinutes(60);
                        $actualEnd = $endTime->copy()->addMinutes(30);
                        $overdue = 'tl_2';
                        $return = 'on_time';
                        $overdueMinutes = 60;
                        $returnMinutes = 0;
                        break;
                }

                // Calculate work duration if both times exist
                $workDuration = 0;
                if ($actualStart && $actualEnd) {
                    $startTimeStr = $actualStart->format('H:i:s');
                    $endTimeStr = $actualEnd->format('H:i:s');
                    
                    // Buat Carbon instance untuk hari yang sama
                    $startTime = Carbon::today()->setTimeFromTimeString($startTimeStr);
                    $endTime = Carbon::today()->setTimeFromTimeString($endTimeStr);
                    
                    // Jika end time lebih kecil dari start time, berarti melewati tengah malam
                    if ($endTime->lessThan($startTime)) {
                        $endTime->addDay();
                    }
                    
                    $workDuration = $startTime->diffInMinutes($endTime);
                }

                // Pastikan work_duration tidak negatif dan dalam menit
                $workDuration = max(0, $workDuration);

                Attendance::create([
                    'user_id' => $user->id,
                    'schedule_latitude' => $schedule->office->latitude,
                    'schedule_longitude' => $schedule->office->longitude,
                    'schedule_start_time' => $schedule->shift->start_time,
                    'schedule_end_time' => $schedule->shift->end_time,
                    'start_latitude' => $schedule->office->latitude,
                    'start_longitude' => $schedule->office->longitude,
                    'start_time' => $actualStart ? $actualStart->format('H:i:s') : null,
                    'end_latitude' => $schedule->office->latitude,
                    'end_longitude' => $schedule->office->longitude,
                    'end_time' => $actualEnd ? $actualEnd->format('H:i:s') : null,
                    'overdue' => $overdue,
                    'overdue_minutes' => $overdueMinutes,
                    'return' => $return,
                    'return_minutes' => $returnMinutes,
                    'work_duration' => $workDuration,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }
} 