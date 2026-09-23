<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Services\Notification\NotificationService;

/** Emits a notification when a purchase is recorded. */
class PurchaseObserver
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function created(Purchase $purchase): void
    {
        $this->notifications->purchaseRecorded(
            (string) ($purchase->invoice_no ?? '#' . $purchase->id),
            (float) $purchase->total,
        );
    }
}
