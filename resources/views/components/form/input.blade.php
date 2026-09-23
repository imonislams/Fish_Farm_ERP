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
. 'focus:outline-none focus:ring-2';
// An errored field keeps its danger colour even while focused, so the ring never
// turns green against a red border (the state must stay legible).
$state = $hasError
? 'border-danger focus:border-danger focus:ring-danger/30'
: 'border-border-strong focus:border-primary focus:ring-primary/40';
@endphp

<input
    @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
    type="{{ $type }}"
    @if ($value !==null) value="{{ $value }}" @endif
    {{ $attributes->merge(['class' => "$base $state"]) }}
    @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>