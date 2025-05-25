<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Schedule;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class Presensi extends Component
{
    use WithFileUploads;

    public $photo;
    public $latitude;
    public $longitude;
    public $schedule;
    public $attendance;
    public $step = 1;
    public $attendanceId = null;
    public $hasTaggedLocation = false;
    public $insideRadius = false;
    public $errorMessage = null;
    public $currentAction = 'arrival'; // 'arrival' atau 'leaving'

    public function mount()
    {
        $this->schedule = Schedule::where('user_id', Auth::id())
            ->first();

        // Cari record attendance hari ini
        $existingAttendance = Attendance::where('user_id', Auth::id())
            ->whereDate('created_at', today())
            ->first();

        if ($existingAttendance) {
            $this->attendance = $existingAttendance;
            $this->attendanceId = $existingAttendance->id;

            // Tentukan currentAction: 'arrival' jika start_time null, 'leaving' jika start_time terisi dan end_time null, 'completed' jika keduanya terisi
            if (is_null($existingAttendance->start_time)) {
                $this->currentAction = 'arrival';
            } elseif (is_null($existingAttendance->end_time)) {
                $this->currentAction = 'leaving';
            } else {
                // Jika start_time dan end_time sudah terisi, presensi hari ini sudah selesai
                $this->currentAction = 'completed';
                $message = 'Anda sudah melakukan presensi datang dan pulang hari ini.';

                // Emit event ke browser untuk menampilkan Swal dan redirect
                $this->dispatch('presensi-completed', message: $message);
            }

            // Tidak perlu set $this->step = 2 di sini lagi.
            // Halaman akan selalu mulai dari step 1 kecuali jika currentAction === 'completed'.
            // Logika di ambilPhoto() dan submitPresensi() akan menyesuaikan berdasarkan currentAction.

        } else {
            // Jika belum ada record hari ini, default ke 'arrival', step tetap 1.
            $this->currentAction = 'arrival';
        }
    }

    public function updatedPhoto()
    {
        if ($this->photo) {
            $this->ambilPhoto();
        }
    }

    public function ambilPhoto()
    {
        // Jangan proses jika presensi hari ini sudah selesai
        if ($this->currentAction === 'completed') {
             $this->errorMessage = 'Presensi hari ini sudah selesai.';
             $this->photo = null;
             return;
        }

        try {
            $this->validate([
                'photo' => 'image|max:1024',
            ]);

            \Log::info('Ambil photo dipanggil', ['photo' => $this->photo ? $this->photo->getFilename() : null]);

            // Cari record attendance hari ini untuk user yang sedang login
            $attendance = Attendance::where('user_id', Auth::id())
                ->whereDate('created_at', today())
                ->first();

            // Tentukan field foto sesuai currentAction
            $photoField = $this->currentAction === 'arrival' ? 'start_attendance_photo' : 'end_attendance_photo';

            // Jika record belum ada untuk hari ini, buat baru
            if (!$attendance) {
                $attendance = new Attendance();
                $attendance->user_id = Auth::id();
                // Set created_at ke waktu sekarang saat record pertama dibuat hari ini
                $attendance->created_at = now();
                // Pada record baru, foto pertama selalu start_attendance_photo
                $photoField = 'start_attendance_photo';
            }

            // Hapus foto lama di kolom yang relevan jika ada
            if ($attendance->{$photoField}) {
                Storage::disk('public')->delete($attendance->{$photoField});
            }

            // Simpan foto baru
            $photoPath = $this->photo->store('attendance-photos', 'public');
            $attendance->{$photoField} = $photoPath;

            // Simpan record (baik create baru atau update yang sudah ada)
            $attendance->save();

            \Log::info('Attendance photo saved', ['attendance_id' => $attendance->id, 'field' => $photoField, 'action' => $this->currentAction]);

            $this->attendance = $attendance; // Update attendance property
            $this->attendanceId = $attendance->id;

            // Jika ini presensi datang dan berhasil simpan foto, update currentAction menjadi leaving
            // Ini agar next action (Tag Location + Submit) akan mengisi kolom end_*
            if ($this->currentAction === 'arrival') {
                 // Tidak langsung pindah step di sini, pindah step setelah submit lokasi
                 // Logika pindah step sudah di submitPresensi
            }

            $this->step = 2;
            $this->errorMessage = null;

            // Reset photo property setelah berhasil upload
            $this->photo = null;

        } catch (\Exception $e) {
            $this->errorMessage = 'Gagal menyimpan foto: ' . $e->getMessage();
            \Log::error('Error ambil photo', ['error' => $e->getMessage()]);
        }
    }

    public function set($property, $value)
    {
        $this->$property = $value;
    }

    public function submitPresensi()
    {
         // Jangan proses jika presensi hari ini sudah selesai
        if ($this->currentAction === 'completed') {
             $this->errorMessage = 'Presensi hari ini sudah selesai.';
             return;
        }
        // Jangan proses jika belum tag lokasi
         if (!$this->latitude || !$this->longitude) {
             $this->errorMessage = 'Silakan tag lokasi terlebih dahulu.';
             return;
         }

        try {
            $attendance = Attendance::find($this->attendanceId);

            if ($attendance) {
                 // Tentukan field waktu dan lokasi sesuai currentAction
                 $timeField = $this->currentAction === 'arrival' ? 'start_time' : 'end_time';
                 $latField = $this->currentAction === 'arrival' ? 'start_latitude' : 'end_latitude';
                 $lngField = $this->currentAction === 'arrival' ? 'start_longitude' : 'end_longitude';

                 $updateData = [
                     $latField => $this->latitude,
                     $lngField => $this->longitude,
                     $timeField => now(),
                 ];

                 // Tambahkan schedule data hanya saat presensi datang
                 if ($this->currentAction === 'arrival') {
                     $updateData['schedule_latitude'] = $this->schedule->office->latitude;
                     $updateData['schedule_longitude'] = $this->schedule->office->longitude;
                     $updateData['schedule_start_time'] = $this->schedule->shift->start_time;
                     $updateData['schedule_end_time'] = $this->schedule->shift->end_time;
                 }

                 $attendance->update($updateData);

                // Refresh variabel $this->attendance agar UI update
                $this->attendance = $attendance->fresh();

                 // Update currentAction jika presensi datang baru selesai
                 if ($this->currentAction === 'arrival') {
                     $this->currentAction = 'leaving'; // Setelah datang, aksi selanjutnya adalah pulang
                 } else if ($this->currentAction === 'leaving'){
                     $this->currentAction = 'completed'; // Setelah pulang, aksi selesai
                 }

                $this->dispatch('presensi-success'); // Tampilkan Swal berhasil
                $this->errorMessage = null; // Clear error
                $this->hasTaggedLocation = false; // Reset tag location state

            } else {
                $this->errorMessage = 'Record presensi tidak ditemukan. Silakan coba lagi dari awal.';
                \Log::error('Error submit presensi: Attendance record not found', ['attendance_id' => $this->attendanceId]);
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Gagal submit presensi: ' . $e->getMessage();
            \Log::error('Error submit presensi', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.presensi');
    }
}