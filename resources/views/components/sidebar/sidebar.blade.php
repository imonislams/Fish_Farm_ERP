@php
/*
| Sidebar navigation.
| Menu is defined in config/navigation.php — do NOT hard-code links here.
| Active state is derived from the current route name (with wildcard support
| via `routeIs()`), so a group stays open on all of its child pages.
*/
$groups = config('navigation', []);

// Resolve whether a menu entry (or one of its children) is currently active.
$isActive = function (array $entry): bool {
if (! empty($entry['route']) && request()->routeIs($entry['route'], $entry['route'].'.*')) {
return true;
}
foreach ($entry['children'] ?? [] as $child) {
if (request()->routeIs($child['route'], $child['route'].'.*')) {
return true;
}
}
return false;
};

/*
 | Permission-aware DISPLAY filter.
 |
 | An entry is shown only if the user holds its `permission` (when one is
 | declared) AND at least one of its children is visible. Entries without a
 | `permission` key remain visible as before.
 |
 | This is PRESENTATION ONLY. Hiding a link is not security — every protected
 | route carries `permission:` middleware. See docs/PERMISSIONS.md.
 */
$canSee = fn (array $item): bool => empty($item['permission'])
    || (auth()->check() && auth()->user()->hasPermission($item['permission']));

$groups = collect($groups)
    ->map(function (array $entry) use ($canSee): array {
        if (! empty($entry['children'])) {
            $entry['children'] = array_values(array_filter($entry['children'], $canSee));
        }

        return $entry;
    })
    // A group with children is visible only if at least one child survived.
    ->filter(fn (array $entry): bool => ! empty($entry['children'])
        || (! empty($entry['route']) && $canSee($entry)))
    ->values()
    ->all();
@endphp

{{-- Mobile overlay --}}
<div
    x-data
    x-show="$store.sidebar.open"
    x-transition.opacity
    @click="$store.sidebar.close()"
    class="fixed inset-0 z-30 bg-black/50 lg:hidden"
    x-cloak></div>

<aside
    x-data
    id="app-sidebar"
    class="gradient-hero fixed inset-y-0 left-0 z-40 flex w-64 flex-col
           transition-transform duration-200 lg:translate-x-0"
    :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full'"
    x-cloak
    aria-label="Main navigation">
    {{-- Brand — company name/logo, never hard-coded --}}
    <div class="flex h-header shrink-0 items-center gap-3 border-b border-white/10 px-4">
        @if ($companyLogo ?? null)
            <img src="{{ $companyLogo }}" alt="{{ $companyName }}"
                 class="h-9 w-9 shrink-0 rounded-control object-cover">
        @else
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-control bg-white/15 text-sm font-bold text-white">
                {{ $companyInitials ?? 'FF' }}
            </span>
        @endif
        <span class="min-w-0 truncate text-sm font-semibold text-white">{{ $companyName ?? config('app.name') }}</span>

        <button
            type="button"
            class="ml-auto rounded p-1 text-white/70 hover:bg-white/10 hover:text-white lg:hidden"
            @click="$store.sidebar.close()"
            aria-label="Close navigation">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Scrollable menu --}}
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Sidebar">
        @foreach ($groups as $index => $entry)
        @php $active = $isActive($entry); @endphp

        @if (empty($entry['children']))
        {{-- Flat link --}}
        <a
            href="{{ Route::has($entry['route']) ? route($entry['route']) : '#' }}"
            @class([ 'flex items-center gap-3 rounded-control px-3 py-2 text-sm font-medium transition-colors' , 'bg-sidebar-active text-white'=> $active,
            'text-sidebar-text hover:bg-sidebar-hover hover:text-white' => ! $active,
            ])
            @if ($active) aria-current="page" @endif
            >
            <x-sidebar.icon :name="$entry['icon']" />
            <span class="truncate">{{ $entry['label'] }}</span>
        </a>
        @else
        {{-- Expandable group --}}
        <div x-data="{ open: {{ $active ? 'true' : 'false' }} }">
            <button
                type="button"
                class="flex w-full items-center gap-3 rounded-control px-3 py-2 text-sm font-medium text-sidebar-text
                               transition-colors hover:bg-sidebar-hover hover:text-white"
                @click="open = !open"
                :aria-expanded="open.toString()">
                <x-sidebar.icon :name="$entry['icon']" />
                <span class="min-w-0 flex-1 truncate text-left">{{ $entry['label'] }}</span>
                <svg
                    class="h-4 w-4 shrink-0 text-sidebar-muted transition-transform duration-200"
                    :class="open ? 'rotate-90' : ''"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="mt-1 space-y-0.5 border-l border-white/10 pl-3 ml-5">
                @foreach ($entry['children'] as $child)
                @php
                $childActive = request()->routeIs($child['route'], $child['route'].'.*');
                @endphp
                <a
                    href="{{ Route::has($child['route']) ? route($child['route']) : '#' }}"
                    @class([ 'block rounded-control px-3 py-1.5 text-sm transition-colors' , 'bg-white/10 font-semibold text-white'=> $childActive,
                    'text-sidebar-muted hover:bg-sidebar-hover hover:text-white' => ! $childActive,
                    ])
                    @if ($childActive) aria-current="page" @endif
                    >
                    {{ $child['label'] }}
                </a>
                @endforeach
            </div>
        </div>
        @endif
        @endforeach
    </nav>

    {{-- Footer / logout --}}
    <div class="shrink-0 border-t border-white/10 p-3">
        @auth
        <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center gap-3 rounded-control px-3 py-2 text-sm font-medium
                           text-sidebar-text transition-colors hover:bg-danger hover:text-white">
                <x-sidebar.icon name="logout" />
                <span>Logout</span>
            </button>
        </form>
        @else
        <a
            href="{{ Route::has('login') ? route('login') : '#' }}"
            class="flex w-full items-center gap-3 rounded-control px-3 py-2 text-sm font-medium
                       text-sidebar-text transition-colors hover:bg-sidebar-hover hover:text-white">
            <x-sidebar.icon name="logout" />
            <span>Login</span>
        </a>
        @endauth
    </div>
</aside>