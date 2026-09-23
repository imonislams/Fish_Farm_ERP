@props(['label' => null, 'name' => null, 'required' => false, 'hint' => null, 'for' => null])

{{--
 | Form field wrapper — handles label, required marker, hint and
 | server-side validation error display in one consistent place.
 |
 | Usage:
 |   <x-form.field label="Pond name" name="name" required>
 |       <x-form.input name="name" :value="old('name')" />
 |   </x-form.field>
 |
 | Laravel validation is authoritative — see docs/ARCHITECTURE.md. Error output
 | is escaped by Blade automatically.
--}}
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
    <label for="{{ $for ?? $name }}" class="block text-sm font-medium text-text-soft">
        {{ $label }}
        @if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif
    </label>
    @endif

    {{ $slot }}

    @if ($hint)
    <p class="text-xs text-muted">{{ $hint }}</p>
    @endif

    @if ($name && $errors->has($name))
    <p class="flex items-start gap-1.5 text-xs font-medium text-danger"
        id="{{ $name }}-error" role="alert">
        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
            viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v4m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z" />
        </svg>
        <span>{{ $errors->first($name) }}</span>
    </p>
    @endif
</div>