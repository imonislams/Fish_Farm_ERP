<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notification centre + the header calendar's month feed.
 *
 * Small, focused endpoints the header calls on demand (never globally shared):
 *   - PATCH /notifications/{id}/read   mark one read
 *   - POST  /notifications/read-all    mark all read
 *   - GET   /calendar/events           the current month's event dots
 *
 * Every query is scoped to the authenticated user via the model scope, so one
 * user can never see another's notifications.
 */
class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly DashboardService $dashboard,
    ) {}

    /** The full list for the "view all" panel. */
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread' => $this->notifications->unreadCount($user?->id),
            'items' => $this->notifications->recent($user?->id, 30),
        ]);
    }

    /** Mark one notification read. */
    public function markRead(Request $request, int $notification): JsonResponse
    {
        $this->notifications->markRead($notification, $request->user()?->id);

        return response()->json([
            'ok' => true,
            'unread' => $this->notifications->unreadCount($request->user()?->id),
        ]);
    }

    /** Mark every notification read. */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->notifications->markAllRead($request->user()?->id);

        return response()->json(['ok' => true, 'marked' => $count, 'unread' => 0]);
    }

    /** The current month's real ERP event dots for the header calendar. */
    public function calendar(Request $request): JsonResponse
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        return response()->json([
            'year' => $year,
            'month' => $month,
            'events' => $this->dashboard->monthEvents($year, $month),
        ]);
    }
}
