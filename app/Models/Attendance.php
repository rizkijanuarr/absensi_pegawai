<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    // Konstanta untuk status presensi datang
    const OVERDUE_ON_TIME = 'on_time';
    const OVERDUE_TL_1 = 'tl_1';
    const OVERDUE_TL_2 = 'tl_2';
    const OVERDUE_NOT_PRESENT = 'not_present';

    // Konstanta untuk status presensi pulang
    const RETURN_ON_TIME = 'on_time';
    const RETURN_PSW_1 = 'psw_1';
    const RETURN_PSW_2 = 'psw_2';
    const RETURN_NOT_PRESENT = 'not_present';

    protected $fillable = [
        'user_id',
        'schedule_latitude',
        'schedule_longitude', 
        'schedule_start_time',
        'schedule_end_time',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'start_time',
        'end_time',
        'start_attendance_photo',
        'end_attendance_photo',
        'overdue',
        'return',
        'overdue_minutes',
        'return_minutes',
        'work_duration',
    ];

    protected $casts = [
        'schedule_latitude' => 'double',
        'schedule_longitude' => 'double',
        'start_latitude' => 'double',
        'start_longitude' => 'double',
        'end_latitude' => 'double',
        'end_longitude' => 'double',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule()
    {
        return $this->belongsTo(\App\Models\Schedule::class);
    }

    public function getShiftAttribute()
    {
        return $this->schedule?->shift?->name;
    }

    // Accessor untuk mengecek status kamera user terkait
    public function getIsUserCameraEnabledAttribute(): bool
    {
        return $this->user?->is_camera_enabled ?? false;
    }

    // Method untuk mendapatkan label status presensi datang
    public function getOverdueStatusLabelAttribute(): string
    {
        return match($this->overdue) {
            self::OVERDUE_ON_TIME => 'TW',
            self::OVERDUE_TL_1 => 'TL 1',
            self::OVERDUE_TL_2 => 'TL 2',
            self::OVERDUE_NOT_PRESENT => 'TH',
            default => 'Belum Presensi'
        };
    }

    // Method untuk mendapatkan label status presensi pulang
    public function getReturnStatusLabelAttribute(): string
    {
        return match($this->return) {
            self::RETURN_ON_TIME => 'TW',
            self::RETURN_PSW_1 => 'PSW 1',
            self::RETURN_PSW_2 => 'PSW 2',
            self::RETURN_NOT_PRESENT => 'TH',
            default => 'Belum Presensi'
        };
    }

    // Method untuk mendapatkan warna badge status presensi datang
    public function getOverdueStatusColorAttribute(): string
    {
        return match($this->overdue) {
            self::OVERDUE_ON_TIME => 'success',
            self::OVERDUE_TL_1 => 'warning',
            self::OVERDUE_TL_2 => 'danger',
            self::OVERDUE_NOT_PRESENT => 'danger',
            default => 'secondary'
        };
    }

    // Method untuk mendapatkan warna badge status presensi pulang
    public function getReturnStatusColorAttribute(): string
    {
        return match($this->return) {
            self::RETURN_ON_TIME => 'success',
            self::RETURN_PSW_1 => 'warning',
            self::RETURN_PSW_2 => 'danger',
            self::RETURN_NOT_PRESENT => 'danger',
            default => 'secondary'
        };
    }

    // Method untuk mengecek apakah presensi hari ini sudah ada
    public static function hasTodayAttendance(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->exists();
    }

    // Method untuk mendapatkan presensi hari ini
    public static function getTodayAttendance(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->first();
    }
}
