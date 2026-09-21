@props([
'name' => null,
'options' => [], // ['value' => 'label'] or [['value' => '', 'label' => '']]
'selected' => null,
'placeholder' => null,
])

@php
$hasError = $name && $errors->has($name);
$base = 'w-full rounded-control border bg-surface px-3 py-2 text-sm text-text '
. 'transition-colors disabled:bg-surface-muted focus:outline-none focus:ring-2 '
. 'focus:ring-primary/40 focus:border-primary';
$state = $hasError ? 'border-danger focus:border-danger focus:ring-danger/30' : 'border-border-strong';

// Normalise the two accepted option shapes into [value => label].
$normalised = [];
foreach ($options as $key => $option) {
if (is_array($option)) {
$normalised[(string) ($option['value'] ?? '')] = $option['label'] ?? '';
} else {
$normalised[(string) $key] = $option;
}
}
@endphp

<select
    @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
    {{ $attributes->merge(['class' => "$base $state"]) }}
    @if ($hasError) aria-invalid="true" @endif>
    @if ($placeholder)
    <option value="">{{ $placeholder }}</option>
    @endif

    @foreach ($normalised as $optionValue => $optionLabel)
    <option value="{{ $optionValue }}" @selected((string) old($name, $selected)===(string) $optionValue)>
        {{ $optionLabel }}
    </option>
    @endforeach

    {{ $slot }}
</select>