{{--
 | Reusable confirmation modal — replaces window.confirm() for destructive or
 | important actions (delete, void, bulk operations).
 |
 | A single instance is mounted in the app layout. Trigger it from any form:
 |
 |   <form method="POST" action="..." data-confirm="Delete this pond?"
 |         data-confirm-title="Delete pond" data-confirm-variant="danger">
 |
 | resources/js/components/confirm-modal.js intercepts the submit and shows
 | this dialog. NEVER use the native confirm() for destructive actions.
--}}
<div
    id="confirm-modal"
    hidden
    class="fixed inset-0 z-[70] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirm-modal-title">
    <div class="absolute inset-0 bg-black/50" data-confirm-cancel></div>

    <div class="surface-card relative w-full max-w-md p-5">
        <div class="flex items-start gap-3">
            <span id="confirm-modal-icon" class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-danger-soft text-danger">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" />
                </svg>
            </span>

            <div class="min-w-0 flex-1">
                <h2 id="confirm-modal-title" class="text-base font-semibold text-text">Please confirm</h2>
                <p id="confirm-modal-message" class="mt-1 text-sm text-muted">
                    This action cannot be undone.
                </p>
            </div>
        </div>

        <div class="mt-5 flex-wrap justify-end gap-2">
            <x-button.button variant="outline" size="sm" data-confirm-cancel>Cancel</x-button.button>
            <button
                type="button"
                id="confirm-modal-accept"
                class="inline-flex items-center justify-center rounded-control bg-danger px-2.5 py-1.5 text-xs font-medium text-white hover:opacity-95">
                Confirm
            </button>
        </div>
    </div>
</div>