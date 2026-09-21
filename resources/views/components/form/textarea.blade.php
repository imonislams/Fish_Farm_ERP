@props(['name' => null, 'value' => null, 'rows' => 3])

@php
$hasError = $name && $errors->has($name);
$base = 'w-full rounded-control border bg-surface px-3 py-2 text-sm text-text '
. 'placeholder:text-muted transition-colors focus:outline-none focus:ring-2 '
. 'focus:ring-primary/40 focus:border-primary';
$state = $hasError ? 'border-danger focus:border-danger focus:ring-danger/30' : 'border-border-strong';
@endphp

<textarea
    @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
    rows="{{ $rows }}"
    {{ $attributes->merge(['class' => "$base $state"]) }}
    @if ($hasError) aria-invalid="true" @endif>{{ $value ?? old($name) }}</textarea>