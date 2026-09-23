<?php

namespace App\Policies;

use App\Models\InspectionSchedule;
use App\Models\User;

/**
 * InspectionSchedule policy — per-record authorization for inspection schedules.
 *
 * The "due date is always derived" rule is a business rule and lives in
 * InspectionScheduleService (the write path); this policy only decides whether the
 * user may act.
 */
class InspectionSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('fcr.schedule.manage');
    }

    public function view(User $user, InspectionSchedule $schedule): bool
    {
        return $user->hasPermission('fcr.schedule.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('fcr.schedule.manage');
    }

    public function update(User $user, InspectionSchedule $schedule): bool
    {
        return $user->hasPermission('fcr.schedule.manage');
    }

    public function delete(User $user, InspectionSchedule $schedule): bool
    {
        return $user->hasPermission('fcr.schedule.manage');
    }
}
