<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Models\Attendance;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Support\Facades\Auth;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

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
            
            // Cek apakah user adalah super admin
            $is_super_admin = Auth::user()->hasRole('super_admin');
            
            // Jika bukan super admin, filter data berdasarkan user_id
            if (!$is_super_admin) {
                $query->where('user_id', Auth::user()->id);
            }

            $badgeCount = is_null($data['status'])
                ? $query->count()
                : $query->where(function($query) use ($data) {
                    if (in_array($data['status'], [Attendance::RETURN_PSW_1, Attendance::RETURN_PSW_2, Attendance::RETURN_NOT_PRESENT])) {
                        $query->where('return', $data['status']);
                    } elseif (in_array($data['status'], [Attendance::OVERDUE_ON_TIME, Attendance::OVERDUE_TL_1, Attendance::OVERDUE_TL_2, Attendance::OVERDUE_NOT_PRESENT])) {
                         $query->where('overdue', $data['status']);
                    }
                    // Handle 'TH' which can be either overdue or return
                    if ($data['status'] === Attendance::OVERDUE_NOT_PRESENT) {
                         $query->orWhere('return', Attendance::RETURN_NOT_PRESENT);
                    }
                })->count();

            return [$key => Tab::make($data['label'])
                ->badge($badgeCount)
                ->modifyQueryUsing(fn ($query) => is_null($data['status']) 
                    ? $query 
                    : $query->where(function($query) use ($data) {
                        if (in_array($data['status'], [Attendance::RETURN_PSW_1, Attendance::RETURN_PSW_2, Attendance::RETURN_NOT_PRESENT])) {
                            $query->where('return', $data['status']);
                        } elseif (in_array($data['status'], [Attendance::OVERDUE_ON_TIME, Attendance::OVERDUE_TL_1, Attendance::OVERDUE_TL_2, Attendance::OVERDUE_NOT_PRESENT])) {
                            $query->where('overdue', $data['status']);
                        }
                         // Handle 'TH' which can be either overdue or return
                         if ($data['status'] === Attendance::OVERDUE_NOT_PRESENT) {
                             $query->orWhere('return', Attendance::RETURN_NOT_PRESENT);
                         }
                    }))
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
