@props(['name' => null, 'value' => null, 'min' => null, 'max' => null])

{{--
 | Date picker — native <input type="date"> styled to match the design system.
 | We deliberately avoid a JS date-picker library: the native control is
 | accessible, keyboard-friendly and works offline. See docs/UI_GUIDELINES.md.
--}}
@php
$hasError = $name && $errors->has($name);
$base = 'w-full rounded-control border bg-surface px-3 py-2 text-sm text-text '
. 'transition-colors focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary';
$state = $hasError ? 'border-danger focus:border-danger focus:ring-danger/30' : 'border-border-strong';
@endphp

<input
    type="date"
    @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
    @if ($value !==null) value="{{ $value }}" @endif
    @if ($min) min="{{ $min }}" @endif
    @if ($max) max="{{ $max }}" @endif
    {{ $attributes->merge(['class' => "$base $state"]) }}
    @if ($hasError) aria-invalid="true" @endif>