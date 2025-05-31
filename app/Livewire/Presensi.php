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
    public $canMarkNotPresent = true; // Tambahkan property ini

    public function mount()
    {
        $user = Auth::user();
        $this->isCameraEnabled = $user->is_camera_enabled;

        $this->schedule = Schedule::where('user_id', $user->id)
            ->first();

        // Cek jika user belum memiliki schedule
        if (is_null($this->schedule)) {
            $message = 'Anda belum memiliki jadwal kerja. Mohon hubungi administrator sistem untuk pengaturan jadwal agar bisa presensi.';
            $this->dispatch('no-schedule', message: $message);
            return;
        }

        // Cari record attendance hari ini
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->first();

        if ($existingAttendance) {
            $this->attendance = $existingAttendance;
            $this->attendanceId = $existingAttendance->id;

            // Cek jika sudah ditandai tidak hadir
            if ($existingAttendance->overdue === Attendance::OVERDUE_NOT_PRESENT) {
                $message = 'Anda telah ditandai sebagai tidak hadir hari ini.';
                $this->dispatch('presensi-completed', message: $message);
                return;
            }

            // Tentukan currentAction
            if (is_null($existingAttendance->start_time)) {
                $this->currentAction = 'arrival';
            } elseif (is_null($existingAttendance->end_time)) {
                $this->currentAction = 'leaving';
            } else {
                $this->currentAction = 'completed';
                $message = 'Anda sudah melakukan presensi datang dan pulang hari ini.';
                $this->dispatch('presensi-completed', message: $message);
                return;
            }

            // Jika ada record (untuk datang atau pulang) dan kamera aktif, mulai dari step 1 untuk foto.
            // Jika kamera nonaktif, langsung ke step 2.
            if ($this->isCameraEnabled) {
                 $this->step = 1;
            } else {
                 $this->step = 2;
            }

            // Disable tombol Tidak Hadir jika sudah ada record
            $this->canMarkNotPresent = false;

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
            // Enable tombol Tidak Hadir jika belum ada record
            $this->canMarkNotPresent = true;
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

            \Log::info('Setelah mencari record attendance', ['attendance_id' => $attendance->id ?? null]);

            // Tentukan field foto sesuai currentAction
            $photoField = $this->currentAction === 'arrival' ? 'start_attendance_photo' : 'end_attendance_photo';

            // Jika record belum ada untuk hari ini, buat baru (hanya untuk presensi datang pertama)
            if (!$attendance) {
                \Log::info('Record attendance belum ada, membuat baru', ['currentAction' => $this->currentAction]);
                if ($this->currentAction !== 'arrival') {
                     // Logika error jika mencoba upload foto pulang tapi record datang belum ada
                     $this->errorMessage = 'Terjadi kesalahan saat mencari record presensi.';
                     \Log::error('Error ambil photo: No attendance record found for non-arrival action', ['user_id' => Auth::id(), 'currentAction' => $this->currentAction]);
                     $this->dispatch('presensi-error', message: $this->errorMessage); // Dispatch error
                     $this->photo = null;
                     return;
                 }
                $attendance = new Attendance();
                $attendance->user_id = Auth::id();
                $attendance->created_at = now(); // Set created_at untuk record pertama
                $photoField = 'start_attendance_photo'; // Pastikan field foto datang

                // Isi schedule data saat membuat record baru
                if ($this->schedule && $this->schedule->office && $this->schedule->shift) {
                    $attendance->schedule_latitude = $this->schedule->office->latitude;
                    $attendance->schedule_longitude = $this->schedule->office->longitude;
                    $attendance->schedule_start_time = $this->schedule->shift->start_time;
                    $attendance->schedule_end_time = $this->schedule->shift->end_time;
                    
                    \Log::info('Schedule data set for new attendance', [
                        'attendance_id' => $attendance->id,
                        'office' => $this->schedule->office->name,
                        'shift' => $this->schedule->shift->name,
                        'start_time' => $this->schedule->shift->start_time,
                        'end_time' => $this->schedule->shift->end_time
                    ]);
                } else {
                    $this->errorMessage = 'Data schedule tidak lengkap untuk membuat record presensi.';
                    \Log::error('Error ambil photo: Data schedule tidak lengkap for new arrival record', [
                        'user_id' => Auth::id(),
                        'has_schedule' => !is_null($this->schedule),
                        'has_office' => $this->schedule ? !is_null($this->schedule->office) : false,
                        'has_shift' => $this->schedule ? !is_null($this->schedule->shift) : false
                    ]);
                    $this->dispatch('presensi-error', message: $this->errorMessage);
                    $this->photo = null;
                    return;
                }

                // Simpan record baru untuk pertama kali agar bisa diupdate selanjutnya
                $attendance->save();
                $this->attendanceId = $attendance->id; // Update attendanceId
                $this->attendance = $attendance; // Update attendance property

                \Log::info('Record attendance baru dibuat', ['attendance_id' => $attendance->id]);

            } else {
                 \Log::info('Record attendance ditemukan', ['attendance_id' => $attendance->id]);
            }

            // Sekarang kita yakin object $attendance sudah ada (baik baru dibuat atau ditemukan)
            if ($attendance) {
                // Hapus foto lama di kolom yang relevan jika ada
                if ($attendance->{$photoField}) {
                    \Log::info('Menghapus foto lama', ['field' => $photoField, 'path' => $attendance->{$photoField}]);
                    // Pastikan disk 'public' sudah disiapkan
                    Storage::disk('public')->delete($attendance->{$photoField});
                    \Log::info('Foto lama berhasil dihapus');
                }

                // Simpan foto baru
                \Log::info('Menyimpan foto baru', ['field' => $photoField]);
                // Ini bisa gagal jika ada masalah permission storage atau konfigurasi disk
                $photoPath = $this->photo->store('attendance-photos', 'public');
                \Log::info('Foto baru berhasil disimpan', ['path' => $photoPath]);

                $attendance->{$photoField} = $photoPath;

                // Simpan record (update yang sudah ada)
                \Log::info('Mengupdate record attendance dengan path foto baru', ['attendance_id' => $attendance->id, 'field' => $photoField, 'path' => $photoPath]);
                $attendance->save();
                \Log::info('Record attendance berhasil diupdate');

                // Refresh variabel $this->attendance agar UI update
                $this->attendance = $attendance->fresh();

                // Setelah berhasil ambil foto, pindah ke step 2
                \Log::info('Mengatur step ke 2');
                $this->step = 2;
                $this->errorMessage = null;

                // Reset photo property setelah berhasil upload
                $this->photo = null;

            } else {
                // Ini seharusnya tidak tercapai dengan logika di atas, tapi jaga-jaga
                 $this->errorMessage = 'Record presensi tidak ditemukan setelah mencoba membuat baru (fallback #2).';
                 \Log::error('Error ambil photo: Attendance record not found after potential creation attempt (fallback #2)', ['user_id' => Auth::id(), 'currentAction' => $this->currentAction]);
                 $this->dispatch('presensi-error', message: $this->errorMessage); // Dispatch error
                 $this->photo = null;
            }
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

    protected function calculateCheckInStatus($attendance)
    {
        \Log::info('Starting calculateCheckInStatus', [
            'attendance_id' => $attendance->id,
            'schedule_start_time' => $attendance->schedule_start_time,
            'actual_start_time' => $attendance->start_time,
            'schedule_latitude' => $attendance->schedule_latitude,
            'schedule_longitude' => $attendance->schedule_longitude,
            'start_latitude' => $attendance->start_latitude,
            'start_longitude' => $attendance->start_longitude
        ]);

        if (!$attendance->schedule_start_time || !$attendance->start_time) {
            \Log::warning('Data waktu tidak lengkap untuk menghitung status presensi datang', [
                'attendance_id' => $attendance->id,
                'has_schedule_start' => !is_null($attendance->schedule_start_time),
                'has_actual_start' => !is_null($attendance->start_time)
            ]);
            
            // Set default values instead of returning null
            $attendance->overdue = Attendance::OVERDUE_NOT_PRESENT;
            $attendance->overdue_minutes = 0;
            return;
        }

        $scheduleStart = Carbon::parse($attendance->schedule_start_time);
        $actualStart = Carbon::parse($attendance->start_time);
        
        \Log::info('Parsed times for check-in status', [
            'attendance_id' => $attendance->id,
            'schedule_start' => $scheduleStart->format('H:i:s'),
            'actual_start' => $actualStart->format('H:i:s')
        ]);

        // Jika absen sebelum jam shift, dianggap tepat waktu
        if ($actualStart->lt($scheduleStart)) {
            $attendance->overdue = Attendance::OVERDUE_ON_TIME;
            $attendance->overdue_minutes = 0;
            \Log::info('Status presensi datang: Tepat Waktu (Sebelum Shift)', [
                'attendance_id' => $attendance->id,
                'schedule_start' => $scheduleStart->format('H:i'),
                'actual_start' => $actualStart->format('H:i')
            ]);
            return;
        }

        // Hitung keterlambatan dalam menit
        $minutesLate = $actualStart->diffInMinutes($scheduleStart, true);

        \Log::info('Calculated late minutes', [
            'attendance_id' => $attendance->id,
            'minutes_late' => $minutesLate
        ]);

        if ($minutesLate <= 60) {
            $attendance->overdue = Attendance::OVERDUE_TL_1;
            $attendance->overdue_minutes = $minutesLate;
            \Log::info('Status presensi datang: Terlambat 1-60 Menit', [
                'attendance_id' => $attendance->id,
                'minutes_late' => $minutesLate
            ]);
        } else {
            $attendance->overdue = Attendance::OVERDUE_TL_2;
            $attendance->overdue_minutes = $minutesLate;
            \Log::info('Status presensi datang: Terlambat > 60 Menit', [
                'attendance_id' => $attendance->id,
                'minutes_late' => $minutesLate
            ]);
        }
    }

    protected function calculateCheckOutStatus($attendance)
    {
        \Log::info('Starting calculateCheckOutStatus', [
            'attendance_id' => $attendance->id,
            'schedule_end_time' => $attendance->schedule_end_time,
            'actual_end_time' => $attendance->end_time,
            'schedule_latitude' => $attendance->schedule_latitude,
            'schedule_longitude' => $attendance->schedule_longitude,
            'end_latitude' => $attendance->end_latitude,
            'end_longitude' => $attendance->end_longitude
        ]);

        if (!$attendance->schedule_end_time || !$attendance->end_time) {
            \Log::warning('Data waktu tidak lengkap untuk menghitung status presensi pulang', [
                'attendance_id' => $attendance->id,
                'has_schedule_end' => !is_null($attendance->schedule_end_time),
                'has_actual_end' => !is_null($attendance->end_time)
            ]);
            return null;
        }

        $scheduleEnd = Carbon::parse($attendance->schedule_end_time);
        $actualEnd = Carbon::parse($attendance->end_time);

        \Log::info('Parsed times for check-out status', [
            'attendance_id' => $attendance->id,
            'schedule_end' => $scheduleEnd->format('H:i:s'),
            'actual_end' => $actualEnd->format('H:i:s')
        ]);

        // Jika pulang tepat waktu atau setelah jam shift, dianggap tepat waktu
        if ($actualEnd->gte($scheduleEnd)) {
            $attendance->return = Attendance::RETURN_ON_TIME;
            $attendance->return_minutes = 0;
            \Log::info('Status presensi pulang: Tepat Waktu', [
                'attendance_id' => $attendance->id,
                'schedule_end' => $scheduleEnd->format('H:i'),
                'actual_end' => $actualEnd->format('H:i')
            ]);
            return;
        }

        // Hitung pulang awal dalam menit
        $minutesEarly = $scheduleEnd->diffInMinutes($actualEnd, true);

        \Log::info('Calculated early minutes', [
            'attendance_id' => $attendance->id,
            'minutes_early' => $minutesEarly
        ]);

        if ($minutesEarly <= 30) {
            $attendance->return = Attendance::RETURN_PSW_1;
            $attendance->return_minutes = $minutesEarly;
            \Log::info('Status presensi pulang: Pulang Awal 1-30 Menit', [
                'attendance_id' => $attendance->id,
                'minutes_early' => $minutesEarly
            ]);
        } else {
            $attendance->return = Attendance::RETURN_PSW_2;
            $attendance->return_minutes = $minutesEarly;
            \Log::info('Status presensi pulang: Pulang Awal 31-60 Menit', [
                'attendance_id' => $attendance->id,
                'minutes_early' => $minutesEarly
            ]);
        }
    }

    protected function calculateWorkDuration($attendance)
    {
        if (!$attendance->start_time || !$attendance->end_time) {
            $attendance->work_duration = 0;
            return;
        }

        // Gunakan full datetime, bukan hanya jam
        $start = \Carbon\Carbon::parse($attendance->start_time);
        $end = \Carbon\Carbon::parse($attendance->end_time);

        // Jika end < start, berarti lewat tengah malam
        if ($end->lessThan($start)) {
            $end->addDay();
        }

        $duration = $start->diffInMinutes($end);

        $attendance->work_duration = $duration;
        \Log::info('Calculated work duration', [
            'attendance_id' => $attendance->id,
            'start_time' => $start,
            'end_time' => $end,
            'duration_minutes' => $attendance->work_duration,
        ]);
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
            \Log::info('Starting submitPresensi', [
                'currentAction' => $this->currentAction,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'attendanceId' => $this->attendanceId
            ]);

            $attendance = Attendance::find($this->attendanceId);

            // Jika record attendance tidak ditemukan dan ini adalah presensi datang, buat record baru.
            if (!$attendance && $this->currentAction === 'arrival') {
                \Log::info('Creating new attendance record for arrival');
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
                    $attendance->work_duration = 0; // Set work_duration ke 0 untuk presensi datang
                    
                    \Log::info('Schedule data set for new attendance', [
                        'attendance_id' => $attendance->id,
                        'office' => $this->schedule->office->name,
                        'shift' => $this->schedule->shift->name,
                        'start_time' => $this->schedule->shift->start_time,
                        'end_time' => $this->schedule->shift->end_time
                    ]);
                } else {
                    $this->errorMessage = 'Data schedule tidak lengkap untuk membuat record presensi.';
                    \Log::error('Error submit presensi: Data schedule tidak lengkap for new arrival record', [
                        'user_id' => Auth::id(),
                        'has_schedule' => !is_null($this->schedule),
                        'has_office' => $this->schedule ? !is_null($this->schedule->office) : false,
                        'has_shift' => $this->schedule ? !is_null($this->schedule->shift) : false
                    ]);
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

                \Log::info('Updating attendance record', [
                    'attendance_id' => $attendance->id,
                    'action' => $this->currentAction,
                    'time_field' => $timeField,
                    'lat_field' => $latField,
                    'lng_field' => $lngField,
                    'update_data' => $updateData
                ]);

                // Update record dengan data lokasi dan waktu
                $attendance->update($updateData);

                // Update status berdasarkan jenis presensi
                if ($this->currentAction === 'arrival') {
                    $this->calculateCheckInStatus($attendance);
                } else {
                    $this->calculateCheckOutStatus($attendance);
                    // Hitung work_duration hanya saat presensi pulang
                    $this->calculateWorkDuration($attendance);
                }

                // Simpan perubahan status
                $attendance->save();

                \Log::info('Attendance record updated successfully', [
                    'attendance_id' => $attendance->id,
                    'overdue' => $attendance->overdue,
                    'return' => $attendance->return,
                    'overdue_minutes' => $attendance->overdue_minutes,
                    'return_minutes' => $attendance->return_minutes,
                    'work_duration' => $attendance->work_duration
                ]);

                // Refresh variabel $this->attendance agar UI update
                $this->attendance = $attendance->fresh();

                // Update currentAction jika presensi datang baru selesai
                if ($this->currentAction === 'arrival') {
                    $this->currentAction = 'leaving'; // Setelah datang, aksi selanjutnya adalah pulang
                } else if ($this->currentAction === 'leaving') {
                    $this->currentAction = 'completed'; // Setelah pulang, aksi selesai
                }

                // Dispatch event success
                $this->dispatch('presensi-success');
                $this->errorMessage = null; // Clear error
                $this->hasTaggedLocation = false; // Reset tag location state

            } else {
                // Ini seharusnya tidak tercapai lagi dengan logika baru, tapi tetap jaga sebagai fallback
                $this->errorMessage = 'Record presensi tidak ditemukan (fallback error). Silakan coba lagi.';
                \Log::error('Error submit presensi: Attendance record not found final fallback', ['attendance_id' => $this->attendanceId]);
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Gagal submit presensi: ' . $e->getMessage();
            \Log::error('Error submit presensi', [
                'error' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
                'currentAction' => $this->currentAction,
                'attendanceId' => $this->attendanceId
            ]);
        }
    }

    public function markAsNotPresent()
    {
        // Dispatch event untuk menampilkan konfirmasi SWAL
        $this->dispatch('tidak-hadir');
    }

    public function confirmNotPresent()
    {
        try {
            // Gunakan Carbon untuk mendapatkan waktu saat ini
            $currentTime = Carbon::now();

            // Cek apakah sudah ada presensi hari ini
            $existingAttendance = Attendance::where('user_id', Auth::id())
                ->whereDate('created_at', today())
                ->first();

            if ($existingAttendance) {
                // Update record yang ada
                $existingAttendance->update([
                    'overdue' => Attendance::OVERDUE_NOT_PRESENT,
                    'overdue_minutes' => 0,
                    'return' => Attendance::RETURN_NOT_PRESENT,
                    'return_minutes' => 0,
                    'start_time' => $currentTime->format('H:i:s'),
                    'end_time' => $currentTime->format('H:i:s'),
                    'work_duration' => 0 // Set work_duration ke 0 untuk tidak hadir
                ]);
            } else {
                // Buat record baru
                Attendance::create([
                    'user_id' => Auth::id(),
                    'overdue' => Attendance::OVERDUE_NOT_PRESENT,
                    'overdue_minutes' => 0,
                    'return' => Attendance::RETURN_NOT_PRESENT,
                    'return_minutes' => 0,
                    'start_time' => $currentTime->format('H:i:s'),
                    'end_time' => $currentTime->format('H:i:s'),
                    'work_duration' => 0, // Set work_duration ke 0 untuk tidak hadir
                    'created_at' => $currentTime
                ]);
            }

            // Dispatch event untuk menampilkan SWAL sukses
            $this->dispatch('confirm-not-present');

        } catch (\Exception $e) {
            $this->errorMessage = 'Gagal menandai tidak hadir: ' . $e->getMessage();
            \Log::error('Error markAsNotPresent', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.presensi');
    }
}