@props([
'name' => null,
'type' => 'text',
'value' => null,
'invalid' => false,
])

@php
$hasError = $invalid || ($name && $errors->has($name));
$base = 'w-full rounded-control border bg-surface px-3 py-2 text-sm text-text '
. 'placeholder:text-muted transition-colors disabled:bg-surface-muted disabled:text-muted '
. 'focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary';
$state = $hasError ? 'border-danger focus:border-danger focus:ring-danger/30' : 'border-border-strong';
@endphp

<input
    @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
    type="{{ $type }}"
    @if ($value !==null) value="{{ $value }}" @endif
    {{ $attributes->merge(['class' => "$base $state"]) }}
    @if ($hasError) aria-invalid="true" @endif>