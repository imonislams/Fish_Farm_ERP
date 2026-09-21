{{--
 | Breadcrumb.
 | Usage:
 |   <x-breadcrumb.breadcrumb :items="[['label' => 'Ponds', 'route' => 'ponds.index'], ['label' => 'New Pond']]" />
 | The last item is treated as the current page (not a link).
--}}
@props(['items' => []])

@if (! empty($items))
<nav aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-1 text-sm text-muted">
        <li>
            <a href="{{ route('dashboard') }}" class="hover:text-primary">Home</a>
        </li>
        @foreach ($items as $item)
        <li class="flex items-center gap-1">
            <svg class="h-4 w-4 text-border-strong" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            @if (! $loop->last && ! empty($item['route']))
            <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}" class="hover:text-primary">
                {{ $item['label'] }}
            </a>
            @else
            <span @if ($loop->last) aria-current="page" @endif class="text-text-soft">
                {{ $item['label'] }}
            </span>
            @endif
        </li>
        @endforeach
    </ol>
</nav>
@endif