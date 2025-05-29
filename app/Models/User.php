<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'is_camera_enabled'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed'
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        // Cek di folder photos
        if (Storage::disk('public')->exists('photos/' . $this->image)) {
            return asset('storage/photos/' . $this->image);
        }

        // Cek di root folder storage
        if (Storage::disk('public')->exists($this->image)) {
            return asset('storage/' . $this->image);
        }

        return null;
    }

    // Relasi ke Schedule (one-to-many)
    public function schedules()
    {
        return $this->hasMany(\App\Models\Schedule::class);
    }

    public function avatar(): Attribute
    {
        return Attribute::make(
            get: function () {
                return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?s=200&d=mp&r=pg';
            }
        );
    }
}
