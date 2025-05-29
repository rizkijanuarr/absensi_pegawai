<?php

namespace App\Filament\Widgets;

use EightyNine\FilamentAdvancedWidget\AdvancedChartWidget;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AdvancedAttendanceLineChart extends AdvancedChartWidget
{
    protected static ?string $heading = 'Presensi';
    protected static string $color = 'info';
    protected static ?string $icon = 'heroicon-o-clipboard-document-check';
    protected static ?string $iconColor = 'info';
    protected static ?string $iconBackgroundColor = 'info';
    protected static ?string $label = 'Total Presensi';

    protected static ?string $badge = 'realtime';
    protected static ?string $badgeColor = 'success';
    protected static ?string $badgeIcon = 'heroicon-o-check-circle';
    protected static ?string $badgeIconPosition = 'after';
    protected static ?string $badgeSize = 'xs';

    public ?string $filter = 'today';

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'Last Week',
            'month' => 'Last Month',
            'year' => 'This Year',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter;
        $query = Attendance::query()->distinct('user_id');
        $data = [];
        $labels = [];
        $totalCount = 0;

        switch ($filter) {
            case 'today':
                $attendances = $query->whereDate('created_at', today())->get();
                $totalCount = $attendances->count();
                $data[] = $totalCount;
                $labels[] = Carbon::now()->format('H:i');
                break;

            case 'week':
                $startDate = Carbon::now()->subDays(6)->startOfDay();
                $endDate = Carbon::now()->endOfDay();

                $attendances = $query->whereBetween('created_at', [$startDate, $endDate])->get();

                $dailyCounts = $attendances->groupBy(function($attendance) {
                    return Carbon::parse($attendance->created_at)->format('Y-m-d');
                })->map->count();

                $totalCount = $dailyCounts->sum();

                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $label = $date->format('D, d M');
                    $labels[] = $label;
                    $dateKey = $date->format('Y-m-d');
                    $data[] = $dailyCounts->has($dateKey) ? $dailyCounts[$dateKey] : 0;
                }
                break;

            case 'month':
                 $startDate = Carbon::now()->subDays(29)->startOfDay();
                 $endDate = Carbon::now()->endOfDay();

                 $attendances = $query->whereBetween('created_at', [$startDate, $endDate])->get();

                 $dailyCounts = $attendances->groupBy(function($attendance) {
                     return Carbon::parse($attendance->created_at)->format('Y-m-d');
                 })->map->count();

                 $totalCount = $dailyCounts->sum();

                 for ($i = 29; $i >= 0; $i--) {
                     $date = Carbon::now()->subDays($i);
                     $label = $date->format('d M');
                     $labels[] = $label;
                     $dateKey = $date->format('Y-m-d');
                     $data[] = $dailyCounts->has($dateKey) ? $dailyCounts[$dateKey] : 0;
                 }
                 break;

            case 'year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();

                $attendances = $query->whereBetween('created_at', [$startDate, $endDate])->get();

                $monthlyCounts = $attendances->groupBy(function($attendance) {
                    return Carbon::parse($attendance->created_at)->format('Y-m');
                })->map->count();

                $totalCount = $monthlyCounts->sum();

                for ($i = 0; $i < 12; $i++) {
                    $month = Carbon::now()->startOfYear()->addMonths($i);
                    $label = $month->format('M');
                    $labels[] = $label;
                    $monthKey = $month->format('Y-m');
                    $data[] = $monthlyCounts->has($monthKey) ? $monthlyCounts[$monthKey] : 0;
                }
                break;
        }

        static::$heading = (string) $totalCount;

        return [
            'datasets' => [
                [
                    'label' => static::$label,
                    'data' => $data,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => '#3B82F650',
                    'fill' => true,
                    'tension' => 0.5
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                         'stepSize' => 1,
                         'callback' => 'function(value) { if (value % 1 === 0) { return value; } }'
                    ],
                ],
            ],
             'plugins' => [
                 'tooltip' => [
                     'enabled' => true
                 ],
             ],
             'maintainAspectRatio' => false,
             'responsive' => true,
             'height' => 700,
        ];
    }
}
