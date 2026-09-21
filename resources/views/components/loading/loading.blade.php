{{--
 | Loading state — spinner + label.
 | Used for lazy-loaded report sections and long-running actions.
--}}
@props(['label' => 'Loading…'])

<div {{ $attributes->merge(['class' => 'flex items-center justify-center gap-3 py-8 text-sm text-muted']) }} role="status">
    <svg class="h-5 w-5 animate-spin text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
    </svg>
    <span>{{ $label }}</span>
</div>