@props([
'variant' => 'primary', // primary | secondary | outline | ghost | danger | success
'size' => 'md', // sm | md
'type' => 'button',
'href' => null,
'icon' => null,
])

@php
$variants = [
'primary' => 'gradient-primary text-white hover:opacity-95',
'secondary' => 'bg-secondary text-white hover:opacity-95',
'outline' => 'border border-border-strong bg-surface text-text-soft hover:bg-surface-muted',
'ghost' => 'text-text-soft hover:bg-surface-muted',
'danger' => 'bg-danger text-white hover:opacity-95',
'success' => 'bg-success text-white hover:opacity-95',
];
$sizes = [
'sm' => 'px-2.5 py-1.5 text-xs',
'md' => 'px-4 py-2 text-sm',
];
$classes = 'inline-flex items-center justify-center gap-2 rounded-control font-medium
transition-colors disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap '
. ($variants[$variant] ?? $variants['primary']) . ' '
. ($sizes[$size] ?? $sizes['md']);
@endphp

{{--
 | Button.
 | Renders an <a> when `href` is provided, otherwise a <button>.
 | Keep colours to the design tokens — do not invent per-page button styles.
--}}
@if ($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if ($icon)<x-sidebar.icon :name="$icon" class="h-4 w-4" />@endif
    {{ $slot }}
</a>
@else
<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if ($icon)<x-sidebar.icon :name="$icon" class="h-4 w-4" />@endif
    {{ $slot }}
</button>
@endif