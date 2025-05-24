<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Schedule;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class Presensi extends Component
{
    public $latitude;
    public $longitude;
    public $insideRadius = false;
    public bool $hasTaggedLocation = false;

    public function render()
    {
        $schedule = Schedule::where('user_id', Auth::id())->first();
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('created_at', today())
            ->first();

        return view('livewire.presensi', compact('schedule', 'attendance'))->with([
            'insideRadius' => $this->insideRadius,
        ]);
    }

    public function store() 
    {
        $this->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $schedule = Schedule::where('user_id', Auth::id())->first();
        if (!$schedule) {
            session()->flash('error', 'Jadwal tidak ditemukan.');
            return;
        }

        $attendance = Attendance::firstOrNew([
            'user_id' => Auth::id(),
            'created_at' => today(),
        ]);

        if (!$attendance->exists) {
            $attendance->fill([
                'schedule_latitude' => $schedule->office->latitude,
                'schedule_longitude' => $schedule->office->longitude,
                'schedule_start_time' => $schedule->shift->start_time,
                'schedule_end_time' => $schedule->shift->end_time,
                'start_latitude' => $this->latitude,
                'start_longitude' => $this->longitude,
                'start_time' => now()->toTimeString(),
                'end_time' => now()->toTimeString(),
            ])->save();
        } else {
            $attendance->update([
                'end_latitude' => $this->latitude,
                'end_longitude' => $this->longitude,
                'end_time' => now()->toTimeString(),
            ]);
        }

        // Reset form setelah berhasil
        $this->hasTaggedLocation = false;
        $this->latitude = null;
        $this->longitude = null;
        
        // Dispatch event untuk menampilkan alert
        $this->dispatch('presensi-success');
    }
}