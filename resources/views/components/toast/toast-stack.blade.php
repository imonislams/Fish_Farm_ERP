{{--
 | Toast stack — renders Laravel session flash messages.
 |
 | Supported session keys: success, error, warning, info
 | (also reads `status` as a success message for Laravel auth compatibility).
 |
 | Messages auto-dismiss via resources/js/components/toast.js and can be
 | closed manually. Rendered automatically by the app + auth layouts.
--}}

@php
$flash = array_filter([
'success' => session('success') ?? session('status'),
'error' => session('error'),
'warning' => session('warning'),
'info' => session('info'),
]);
@endphp

<div
    id="toast-stack"
    class="pointer-events-none fixed right-4 top-4 z-[60] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2"
    aria-live="polite"
    aria-atomic="true">
    @foreach ($flash as $tone => $message)
    <div
        class="toast pointer-events-auto surface-card flex items-start gap-3 border-l-4 p-3"
        data-tone="{{ $tone }}"
        role="status"
        style="border-left-color: var(--color-{{ $tone === 'error' ? 'danger' : $tone }})">
        <span class="text-sm text-text-soft">{{ $message }}</span>

        <button
            type="button"
            class="ml-auto shrink-0 rounded p-0.5 text-muted hover:text-text"
            data-toast-close
            aria-label="Dismiss notification">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    @endforeach
</div>