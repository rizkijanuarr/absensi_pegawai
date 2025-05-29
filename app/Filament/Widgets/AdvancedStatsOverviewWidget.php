<?php

namespace App\Filament\Widgets;

use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Office;
use App\Models\Shift;
use App\Models\Attendance;
use App\Models\Schedule;

class AdvancedStatsOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Pegawai Aktif', User::count())
                ->icon('heroicon-o-users')
                ->description('Total pegawai aktif')
                ->descriptionIcon('heroicon-o-chevron-up', 'before')
                ->descriptionColor('success')
                ->iconColor('success'),
            Stat::make('Total Kantor', Office::count())
                ->icon('heroicon-o-building-office')
                ->description('Total kantor')
                ->descriptionIcon('heroicon-o-chevron-up', 'before')
                ->descriptionColor('success')
                ->iconColor('success'),
            Stat::make('Total Shift', Shift::count())
                ->icon('heroicon-o-clock')
                ->description('Total shift')
                ->descriptionIcon('heroicon-o-chevron-up', 'before')
                ->descriptionColor('warning')
                ->iconColor('warning'),
            Stat::make('Total Presensi', Attendance::count())
                ->icon('heroicon-o-clipboard-document-check')
                ->description('Total presensi')
                ->descriptionIcon('heroicon-o-chevron-up', 'before')
                ->descriptionColor('danger')
                ->iconColor('danger'),
            Stat::make('Total Schedule', Schedule::count())
                ->icon('heroicon-o-calendar')
                ->description('Total schedule')
                ->descriptionIcon('heroicon-o-chevron-up', 'before')
                ->descriptionColor('success')
                ->iconColor('info'),
        ];
    }
}
