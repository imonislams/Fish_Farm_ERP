@props(['tone' => 'default', 'dot' => false])

@php
$tones = [
'default' => 'bg-surface-muted text-text-soft border-border',
'primary' => 'bg-primary/10 text-primary border-primary/20',
'success' => 'bg-success-soft text-success border-success/20',
'warning' => 'bg-warning-soft text-warning border-warning/20',
'danger' => 'bg-danger-soft text-danger border-danger/20',
'info' => 'bg-info-soft text-info border-info/20',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium ' . ($tones[$tone] ?? $tones['default'])]) }}>
    @if ($dot)
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    @endif
    {{ $slot }}
</span>