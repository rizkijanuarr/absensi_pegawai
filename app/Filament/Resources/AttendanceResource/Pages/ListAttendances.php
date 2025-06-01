<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Models\Attendance;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    private function applyStatusFilter(Builder $query, ?string $status): Builder
    {
        if (is_null($status)) {
            return $query;
        }

        return $query->where(function($query) use ($status) {
            if (in_array($status, [Attendance::RETURN_PSW_1, Attendance::RETURN_PSW_2, Attendance::RETURN_NOT_PRESENT])) {
                $query->where('return', $status);
            } elseif (in_array($status, [Attendance::OVERDUE_ON_TIME, Attendance::OVERDUE_TL_1, Attendance::OVERDUE_TL_2, Attendance::OVERDUE_NOT_PRESENT])) {
                $query->where('overdue', $status);
            }
            if ($status === Attendance::OVERDUE_NOT_PRESENT) {
                $query->orWhere('return', Attendance::RETURN_NOT_PRESENT);
            }
        });
    }

    public function getTabs(): array
    {
        $statuses = collect([
            'all' => ['label' => 'All', 'badgeColor' => 'primary', 'status' => null],
            Attendance::OVERDUE_ON_TIME => ['label' => 'TW', 'badgeColor' => 'success', 'status' => Attendance::OVERDUE_ON_TIME],
            Attendance::OVERDUE_TL_1 => ['label' => 'TL 1', 'badgeColor' => 'warning', 'status' => Attendance::OVERDUE_TL_1],
            Attendance::OVERDUE_TL_2 => ['label' => 'TL 2', 'badgeColor' => 'danger', 'status' => Attendance::OVERDUE_TL_2],
            Attendance::RETURN_PSW_1 => ['label' => 'PSW 1', 'badgeColor' => 'warning', 'status' => Attendance::RETURN_PSW_1],
            Attendance::RETURN_PSW_2 => ['label' => 'PSW 2', 'badgeColor' => 'danger', 'status' => Attendance::RETURN_PSW_2],
            Attendance::OVERDUE_NOT_PRESENT => ['label' => 'TH', 'badgeColor' => 'danger', 'status' => Attendance::OVERDUE_NOT_PRESENT],
        ]);

        return $statuses->mapWithKeys(function ($data, $key) {
            $query = Attendance::query();
            
            if (!Auth::user()->hasRole('super_admin')) {
                $query->where('user_id', Auth::user()->id);
            }

            $badgeCount = $this->applyStatusFilter($query->clone(), $data['status'])->count();

            return [$key => Tab::make($data['label'])
                ->badge($badgeCount)
                ->modifyQueryUsing(fn ($query) => $this->applyStatusFilter($query, $data['status']))
                ->badgeColor($data['badgeColor'])];
        })->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Tambah Presensi')
                ->url(route('presensi'))
                ->color('success'),
            Actions\CreateAction::make(),
        ];
    }
}
