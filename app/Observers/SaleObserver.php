<?php

namespace App\Observers;

use App\Models\Sale;
use App\Services\Notification\NotificationService;

/**
 * Emits a notification when a sale is recorded — a REAL event, never faked.
 *
 * Uses the model's `created` event so it fires for every write path (once), and
 * never interferes with the transactional business logic in SalesService.
 */
class SaleObserver
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function created(Sale $sale): void
    {
        $this->notifications->saleRecorded(
            (string) $sale->invoice_no,
            (float) $sale->total,
            $sale->customer_id,
        );
    }
}
