{{--
 | Pagination.
 | Renders Laravel's paginator with the app's design tokens.
 | Usage: <x-pagination.pagination :paginator="$ponds" />
--}}
@props(['paginator' => null])

@if ($paginator && $paginator->hasPages())
<nav
    role="navigation"
    aria-label="Pagination"
    class="flex flex-col gap-3 border-t border-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-xs text-muted">
        Showing
        <span class="font-medium text-text-soft">{{ $paginator->firstItem() }}</span>
        to
        <span class="font-medium text-text-soft">{{ $paginator->lastItem() }}</span>
        of
        <span class="font-medium text-text-soft">{{ $paginator->total() }}</span>
        results
    </p>

    <div class="flex flex-wrap items-center gap-1">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
        <span class="cursor-not-allowed rounded-control border-border px-3 py-1.5 text-xs text-muted">
            Previous
        </span>
        @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
            class="rounded-control border-border px-3 py-1.5 text-xs text-text-soft hover:bg-surface-muted">
            Previous
        </a>
        @endif

        {{-- Page numbers (windowed by Laravel) --}}
        @foreach ($paginator->links()->elements[0] ?? [] as $page => $url)
        @if ($page == $paginator->currentPage())
        <span aria-current="page"
            class="gradient-primary rounded-control px-3 py-1.5 text-xs font-semibold text-white">
            {{ $page }}
        </span>
        @else
        <a href="{{ $url }}"
            class="rounded-control border-border px-3 py-1.5 text-xs text-text-soft hover:bg-surface-muted">
            {{ $page }}
        </a>
        @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next"
            class="rounded-control border-border px-3 py-1.5 text-xs text-text-soft hover:bg-surface-muted">
            Next
        </a>
        @else
        <span class="cursor-not-allowed rounded-control border-border px-3 py-1.5 text-xs text-muted">
            Next
        </span>
        @endif
    </div>
</nav>
@endif