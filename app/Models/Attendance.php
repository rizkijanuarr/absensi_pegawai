<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

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

}
