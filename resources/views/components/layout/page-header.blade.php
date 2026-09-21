{{--
 | Page header — hero band with title, subtitle, breadcrumb and action slot.
 | Usage:
 |   <x-layout.page-header title="All Ponds" subtitle="...">
 |       <x-slot:actions><x-button.button ... /></x-slot:actions>
 |   </x-layout.page-header>
--}}
@props(['title' => null, 'subtitle' => null, 'breadcrumb' => []])

<div class="gradient-hero rounded-card px-5 py-5 text-white sm:px-6 sm:py-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div class="min-w-0">
            @if (! empty($breadcrumb))
            <div class="mb-2 [&_a]:text-white/75 [&_a:hover]:text-white [&_span]:text-white/90 [&_svg]:text-white/40">
                <x-breadcrumb.breadcrumb :items="$breadcrumb" />
            </div>
            @endif

            @if ($title)
            <h2 class="truncate text-xl font-bold sm:text-2xl">{{ $title }}</h2>
            @endif

            @if ($subtitle)
            <p class="mt-1 text-sm text-white/75">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($actions)
        {{-- Actions must wrap, never clip, on small screens --}}
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
        @endisset
    </div>
</div>