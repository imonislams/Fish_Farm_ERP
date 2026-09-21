{{--
 | Generic card / panel with optional title and actions.
 | Usage:
 |   <x-card.card title="Pond Status">
 |       <x-slot:actions>...</x-slot:actions>
 |       content
 |   </x-card.card>
--}}
@props(['title' => null, 'subtitle' => null, 'padded' => true, 'tone' => 'default'])

@php
$border = $tone === 'danger' ? 'border-danger/40' : ($tone === 'warning' ? 'border-warning/40' : 'border-border');
@endphp

<section {{ $attributes->merge(['class' => "surface-card border $border overflow-hidden"]) }}>
    @if ($title || isset($actions))
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 py-3">
        <div class="min-w-0">
            @if ($title)
            <h3 class="truncate text-sm font-semibold text-text">{{ $title }}</h3>
            @endif
            @if ($subtitle)
            <p class="mt-0.5 text-xs text-muted">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </header>
    @endif

    <div @class(['p-4'=> $padded])>
        {{ $slot }}
    </div>
</section>