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
    public $isCameraEnabled = true; // Tambahkan properti ini

    public function mount()
    {
        $user = Auth::user(); // Dapatkan user yang sedang login
        $this->isCameraEnabled = $user->is_camera_enabled; // Ambil status kamera user

        $this->schedule = Schedule::where('user_id', $user->id)
            ->first();

        // Cek jika user belum memiliki schedule
        if (is_null($this->schedule)) {
            $message = 'Anda belum memiliki jadwal kerja. Mohon hubungi administrator sistem untuk pengaturan jadwal agar bisa presensi.';
            $this->dispatch('no-schedule', message: $message);
            
            // Mungkin perlu menghentikan eksekusi Livewire mount lebih lanjut
            // Meskipun dispatch event, Livewire mungkin tetap menjalankan sisa mount() dan render()
            // Redirect di SweetAlert client-side akan menangani navigasi.
            return; // Hentikan mount process
        }

        // Cari record attendance hari ini
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->first();

        if ($existingAttendance) {
            $this->attendance = $existingAttendance;
            $this->attendanceId = $existingAttendance->id;

            // Tentukan currentAction
            if (is_null($existingAttendance->start_time)) {
                $this->currentAction = 'arrival';
            } elseif (is_null($existingAttendance->end_time)) {
                $this->currentAction = 'leaving';
            } else {
                $this->currentAction = 'completed';
                $message = 'Anda sudah melakukan presensi datang dan pulang hari ini.';
                $this->dispatch('presensi-completed', message: $message);
                return; // Hentikan mount jika sudah selesai
            }

            // Jika ada record (untuk datang atau pulang) dan kamera aktif, mulai dari step 1 untuk foto.
            // Jika kamera nonaktif, langsung ke step 2.
            if ($this->isCameraEnabled) {
                 $this->step = 1;
            } else {
                 $this->step = 2;
            }

        } else {
            // Jika belum ada record hari ini, default ke 'arrival'.
            $this->currentAction = 'arrival';
            // Jika kamera dinonaktifkan untuk user ini, langsung ke step 2.
            if (!$this->isCameraEnabled) {
                 $this->step = 2;
             } else {
                // Jika belum ada record dan kamera aktif, mulai dari step 1.
                 $this->step = 1;
             }
        }
         \Log::info('Mount finished', ['currentAction' => $this->currentAction, 'step' => $this->step, 'isCameraEnabled' => $this->isCameraEnabled]);
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
            // Jika ini presensi datang ($this->currentAction === 'arrival'), fieldnya start_attendance_photo
            // Jika ini presensi pulang ($this->currentAction === 'leaving'), fieldnya end_attendance_photo
            $photoField = $this->currentAction === 'arrival' ? 'start_attendance_photo' : 'end_attendance_photo';

            // Jika record belum ada untuk hari ini, buat baru (hanya untuk presensi datang pertama)
            // Ini seharusnya hanya terjadi saat $this->currentAction === 'arrival'
            if (!$attendance) {
                if ($this->currentAction !== 'arrival') {
                     // Logika error, seharusnya ada record jika currentAction bukan arrival saat ini
                     $this->errorMessage = 'Terjadi kesalahan saat mencari record presensi.';
                     \Log::error('Error ambil photo: No attendance record found for non-arrival action', ['user_id' => Auth::id(), 'currentAction' => $this->currentAction]);
                     $this->photo = null;
                     return;
                 }
                $attendance = new Attendance();
                $attendance->user_id = Auth::id();
                // Set created_at ke waktu sekarang saat record pertama dibuat hari ini
                $attendance->created_at = now();
                // Pada record baru, foto pertama selalu start_attendance_photo
                $photoField = 'start_attendance_photo'; // Pastikan field foto datang
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
            $this->attendanceId = $attendance->id; // Pastikan ID terisi

            // Setelah berhasil ambil foto (baik datang atau pulang), pindah ke step 2
            $this->step = 2;
            $this->errorMessage = null;

            // Reset photo property setelah berhasil upload
            $this->photo = null;

        } catch (\Exception $e) {
            $this->errorMessage = 'Gagal menyimpan foto: ' . $e->getMessage();
            \Log::error('Error ambil photo', ['error' => $e->getMessage()]);
             // Reset photo property jika gagal upload agar bisa coba lagi
             $this->photo = null;
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

            // Jika record attendance tidak ditemukan dan ini adalah presensi datang, buat record baru.
            if (!$attendance && $this->currentAction === 'arrival') {
                 $attendance = new Attendance();
                 $attendance->user_id = Auth::id();
                 // Set created_at ke waktu sekarang saat record pertama dibuat hari ini
                 $attendance->created_at = now();
                 // Schedule data harus diisi di sini untuk presensi datang pertama
                 if ($this->schedule && $this->schedule->office && $this->schedule->shift) {
                     $attendance->schedule_latitude = $this->schedule->office->latitude;
                     $attendance->schedule_longitude = $this->schedule->office->longitude;
                     $attendance->schedule_start_time = $this->schedule->shift->start_time;
                     $attendance->schedule_end_time = $this->schedule->shift->end_time;
                 } else {
                      $this->errorMessage = 'Data schedule tidak lengkap untuk membuat record presensi.';
                      \Log::error('Error submit presensi: Data schedule tidak lengkap for new arrival record', ['user_id' => Auth::id()]);
                      return;
                 }
                 $attendance->save(); // Simpan record baru untuk mendapatkan ID
                 $this->attendanceId = $attendance->id; // Update attendanceId
                 $this->attendance = $attendance; // Update attendance property
                 \Log::info('New attendance record created for arrival.', ['attendance_id' => $attendance->id]);

            } else if (!$attendance && $this->currentAction !== 'arrival') {
                // Ini adalah skenario error: mencoba submit pulang/lain tapi tidak ada record datang hari ini
                 $this->errorMessage = 'Record presensi datang tidak ditemukan. Silakan coba lagi dari awal.';
                 \Log::error('Error submit presensi: No arrival record found for non-arrival action', ['user_id' => Auth::id(), 'currentAction' => $this->currentAction]);
                 return;
            }

            // Sekarang kita yakin object $attendance sudah ada (baik dicari atau baru dibuat)

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

                // Update record dengan data lokasi dan waktu
                 $attendance->update($updateData);

                // Refresh variabel $this->attendance agar UI update
                $this->attendance = $attendance->fresh();

                 // Update currentAction jika presensi datang baru selesai
                 if ($this->currentAction === 'arrival') {
                     $this->currentAction = 'leaving'; // Setelah datang, aksi selanjutnya adalah pulang
                 } else if ($this->currentAction === 'leaving') {
                     $this->currentAction = 'completed'; // Setelah pulang, aksi selesai
                 }

                $this->dispatch('presensi-success'); // Tampilkan Swal berhasil
                $this->errorMessage = null; // Clear error
                $this->hasTaggedLocation = false; // Reset tag location state

            } else {
                // Ini seharusnya tidak tercapai lagi dengan logika baru, tapi tetap jaga sebagai fallback
                $this->errorMessage = 'Record presensi tidak ditemukan (fallback error). Silakan coba lagi.';
                \Log::error('Error submit presensi: Attendance record not found final fallback', ['attendance_id' => $this->attendanceId]);
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Gagal submit presensi: ' . $e->getMessage();
            \Log::error('Error submit presensi', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    public function render()
    {
        return view('livewire.presensi');
    }
}