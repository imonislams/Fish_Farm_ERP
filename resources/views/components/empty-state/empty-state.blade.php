{{--
 | Empty state — shown when a list/report has no rows.
 | Every list view must render this instead of an empty <tbody>.
--}}
@props([
'title' => 'No records found',
'message' => 'There is nothing to display yet.',
'icon' => 'search',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-4 py-12 text-center']) }}>
    <span class="grid h-12 w-12 place-items-center rounded-full bg-surface-muted text-muted">
        <x-sidebar.icon :name="$icon" class="h-6 w-6" />
    </span>

    <h3 class="mt-4 text-sm font-semibold text-text">{{ $title }}</h3>
    <p class="mt-1 max-w-sm text-sm text-muted">{{ $message }}</p>

    @isset($actions)
    <div class="mt-5 flex-wrap items-center justify-center gap-2">{{ $actions }}</div>
    @endisset
</div>