@php
/*
| Reusable "module pending" panel.
|
| Modules are scaffolded progressively. Rather than shipping fake data or a
| broken page, every not-yet-implemented module renders this honest state.
| Flip the module flag in config/fishfarm.php once it is real.
*/
@endphp

<x-layout.page-header
    :title="$title ?? 'Module'"
    :subtitle="$subtitle ?? null"
    :breadcrumb="$breadcrumb ?? []" />

<div class="mt-5">
    <x-card.card :title="$title ?? 'Module'">
        <div class="flex flex-col items-center justify-center py-10 text-center">
            <span class="grid h-12 w-12 place-items-center rounded-full bg-warning-soft text-warning">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>

            <h3 class="mt-4 text-sm font-semibold text-text">This module is not implemented yet</h3>

            <p class="mt-1 max-w-md text-sm text-muted">
                {{ $description ?? 'The architecture, routes and design system for this area are in place.' }}
                No data is shown because none exists yet — the ERP never displays placeholder business figures.
            </p>

            <div class="mt-5 flex-wrap items-center justify-center gap-2">
                <x-button.button :href="route('dashboard')" variant="outline" size="sm">Back to dashboard</x-button.button>
                <x-badge.badge tone="info">Architecture: ready</x-badge.badge>
                <x-badge.badge tone="warning">Implementation: pending</x-badge.badge>
            </div>
        </div>
    </x-card.card>
</div>