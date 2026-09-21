@props([
'label' => '',
'value' => '0',
'icon' => 'grid',
'hint' => null,
'trend' => null, // 'up' | 'down' | null
'tone' => 'primary', // primary | success | warning | danger | info | secondary
])

@php
$tones = [
'primary' => ['bg' => 'bg-primary/10', 'text' => 'text-primary'],
'secondary' => ['bg' => 'bg-secondary/10', 'text' => 'text-secondary'],
'success' => ['bg' => 'bg-success/10', 'text' => 'text-success'],
'warning' => ['bg' => 'bg-warning/10', 'text' => 'text-warning'],
'danger' => ['bg' => 'bg-danger/10', 'text' => 'text-danger'],
'info' => ['bg' => 'bg-info/10', 'text' => 'text-info'],
];
$t = $tones[$tone] ?? $tones['primary'];
@endphp

{{--
 | KPI card — the standard dashboard metric tile.
 | Dashboard values must come from services/query objects, never hard-coded.
--}}
<div {{ $attributes->merge(['class' => 'surface-card surface-card--hoverable p-4']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-xs font-medium uppercase tracking-wide text-muted">{{ $label }}</p>
            <p class="mt-2 truncate text-2xl font-bold text-text">{{ $value }}</p>

            @if ($hint || $trend)
            <p class="mt-1 flex items-center gap-1 text-xs
                          {{ $trend === 'up' ? 'text-success' : ($trend === 'down' ? 'text-danger' : 'text-muted') }}">
                @if ($trend === 'up')
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                </svg>
                @elseif ($trend === 'down')
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
                @endif
                {{ $hint }}
            </p>
            @endif
        </div>

        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-control {{ $t['bg'] }} {{ $t['text'] }}">
            <x-sidebar.icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
</div>