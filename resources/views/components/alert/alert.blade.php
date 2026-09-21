@props(['message' => null, 'tone' => 'info'])

@php
$tones = [
'success' => ['bg' => 'bg-success-soft', 'text' => 'text-success', 'border' => 'border-success/30',
'icon' => 'M5 13l4 4L19 7'],
'error' => ['bg' => 'bg-danger-soft', 'text' => 'text-danger', 'border' => 'border-danger/30',
'icon' => 'M6 18L18 6M6 6l12 12'],
'warning' => ['bg' => 'bg-warning-soft', 'text' => 'text-warning', 'border' => 'border-warning/30',
'icon' => 'M12 9v4m0 4h.01M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z'],
'info' => ['bg' => 'bg-info-soft', 'text' => 'text-info', 'border' => 'border-info/30',
'icon' => 'M12 9v4m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z'],
];
$t = $tones[$tone] ?? $tones['info'];
@endphp

{{--
 | Inline alert (persistent, in-page).
 | Use <x-toast.toast-stack /> for transient flash feedback instead.
--}}
<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-control border px-4 py-3 {$t['bg']} {$t['border']}"]) }} role="alert">
    <svg class="mt-0.5 h-5 w-5 shrink-0 {{ $t['text'] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $t['icon'] }}" />
    </svg>

    <div class="min-w-0 flex-1 text-sm {{ $t['text'] }}">
        @if ($message){{ $message }}@else{{ $slot }}@endif
    </div>

    @isset($actions)
    <div class="shrink-0">{{ $actions }}</div>
    @endisset
</div>