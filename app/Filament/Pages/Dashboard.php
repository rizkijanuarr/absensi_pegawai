<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\AdvancedStatsOverviewWidget;
use App\Filament\Widgets\AdvancedAttendanceLineChart;
use App\Filament\Widgets\AdvancedTableWidget;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        $user = Auth::user();
        
        // Jika user adalah karyawan dan tidak memiliki schedule, jangan tampilkan widget
        // PERBAIKAN: Gunakan 'Karyawan' dengan huruf K besar
        if ($user && $user->hasRole('Karyawan') && $user->schedules()->count() === 0) {
            return [];
        }

        return [
            AdvancedStatsOverviewWidget::class,
            AdvancedAttendanceLineChart::class,
            AdvancedTableWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    // Method untuk cek apakah user adalah karyawan tanpa schedule
    public function isKaryawanWithoutSchedule(): bool
    {
        $user = Auth::user();
        // PERBAIKAN: Gunakan 'Karyawan' dengan huruf K besar
        return $user && $user->hasRole('Karyawan') && $user->schedules()->count() === 0;
    }

    // Method helper untuk mendapatkan user info
    public function getUserName(): string
    {
        return Auth::user()->name ?? 'User';
    }
}