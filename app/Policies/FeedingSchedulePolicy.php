<?php

namespace App\Policies;

use App\Models\FeedingSchedule;
use App\Models\User;

/**
 * FeedingSchedule (meal plan) policy — per-record authorization.
 */
class FeedingSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('feed.view');
    }

    public function view(User $user, FeedingSchedule $schedule): bool
    {
        return $user->hasPermission('feed.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('feed.schedule.manage');
    }

    public function update(User $user, FeedingSchedule $schedule): bool
    {
        return $user->hasPermission('feed.schedule.manage');
    }

    public function delete(User $user, FeedingSchedule $schedule): bool
    {
        return $user->hasPermission('feed.schedule.manage');
    }
}
