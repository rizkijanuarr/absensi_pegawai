<?php

namespace App\Traits;

trait HasNavigationBadge
{
    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }
}