<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasNavigationBadge
{
    public static function getNavigationBadge(): ?string
    {
        $query = static::$model::query();
        
        // Cek apakah user adalah super admin
        $is_super_admin = Auth::user()->hasRole('super_admin');
        
        // Jika bukan super admin, filter data berdasarkan user_id
        if (!$is_super_admin) {
            $query->where('user_id', Auth::user()->id);
        }
        
        return $query->count();
    }
}