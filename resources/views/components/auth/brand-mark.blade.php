@props([
'logo' => null,
'initials' => 'FF',
'gradient' => false,
])

{{--
 | Brand mark — the company logo when one is uploaded, otherwise the company's
 | initials. Never a placeholder image, never a hard-coded letter: the identity
 | comes from \App\Support\CompanyContext (the ONE company source).
 |
 | Usage:
 |   <x-auth.brand-mark :logo="$brandLogo" :initials="$brandInitials" class="h-11 w-11" />
 |
 | `gradient` renders the coloured mark used on light backgrounds; without it the
 | mark renders translucent white for use on the dark brand panel.
--}}

@php
$base = 'grid shrink-0 place-items-center overflow-hidden rounded-control font-bold ';
$tone = $gradient
? 'gradient-primary text-white'
: 'bg-white/15 text-white';
@endphp

<span {{ $attributes->merge(['class' => $base . $tone]) }}>
    @if ($logo)
    <img src="{{ $logo }}" alt="" class="h-full w-full object-cover">
    @else
    <span aria-hidden="true">{{ $initials }}</span>
    @endif
</span>