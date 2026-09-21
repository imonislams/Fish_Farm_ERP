<x-layout.app :title="$title ?? 'Module'">

    <x-layout.page-header :title="$title ?? 'Module'" />

    <div class="mt-5">
        <x-card.card :title="$title ?? 'Module'">
            <div class="flex flex-col items-center justify-center py-10 text-center">
                <span class="grid h-12 w-12 place-items-center rounded-full bg-warning-soft text-warning">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>

                <h3 class="mt-4 text-sm font-semibold text-text">This module is not implemented yet</h3>

                <p class="mt-1 max-w-lg text-sm text-muted">
                    The route, navigation entry and design system for this area are already in place.
                    No records are shown because the tables do not exist yet &mdash; the ERP never
                    displays placeholder business data.
                </p>

                <div class="mt-4 flex-wrap items-center justify-center gap-2">
                    <x-badge.badge tone="success">Route: ready</x-badge.badge>
                    <x-badge.badge tone="info">Architecture: ready</x-badge.badge>
                    <x-badge.badge tone="warning">Implementation: pending</x-badge.badge>
                </div>

                @if (! empty($routeName))
                <p class="mt-4 text-xs text-muted">
                    Route name: <code class="rounded bg-surface-muted px-1.5 py-0.5">{{ $routeName }}</code>
                </p>
                @endif

                <div class="mt-5">
                    <x-button.button :href="route('dashboard')" variant="outline" size="sm">
                        Back to dashboard
                    </x-button.button>
                </div>
            </div>
        </x-card.card>
    </div>

</x-layout.app>