<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use SoftDeletes;

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

        if (Storage::disk('public')->exists('photos/' . $this->image)) {
            return asset('storage/photos/' . $this->image);
        }

        if (Storage::disk('public')->exists($this->image)) {
            return asset('storage/' . $this->image);
        }

        return null;
    }

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
