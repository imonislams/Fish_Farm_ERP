@props(['title' => null])

{{--
 | Sticky top header.
 | Contains the mobile menu toggle, the current page title, the live network
 | status indicator and the user menu. Keep business logic out of here.
--}}
<header class="sticky top-0 z-20 flex h-header shrink-0 items-center gap-3 border-b border-border bg-surface px-4 sm:px-6 lg:px-8">

    {{-- Mobile: open sidebar --}}
    <button
        type="button"
        class="rounded-control p-2 text-text-soft hover:bg-surface-muted lg:hidden"
        @click="$store.sidebar.open()"
        aria-label="Open navigation">
        <x-sidebar.icon name="menu" />
    </button>

    <h1 class="min-w-0 flex-1 truncate text-base font-semibold text-text">
        {{ $title ?? 'Dashboard' }}
    </h1>

    {{-- Live network status (PWA requirement — see docs/PWA.md) --}}
    <x-layout.network-indicator />

    {{-- Notifications --}}
    <button
        type="button"
        class="relative rounded-control p-2 text-text-soft hover:bg-surface-muted"
        aria-label="Notifications">
        <x-sidebar.icon name="bell" />
    </button>

    {{-- User menu --}}
    @auth
    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
        <button
            type="button"
            class="flex items-center gap-2 rounded-control px-2 py-1.5 hover:bg-surface-muted"
            @click="open = !open"
            :aria-expanded="open.toString()"
            aria-haspopup="menu">
            <span class="gradient-primary grid h-8 w-8 place-items-center rounded-full text-xs font-bold text-white">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </span>
            <span class="hidden truncate text-sm font-medium sm:block">{{ auth()->user()->name }}</span>
        </button>

        <div
            x-show="open"
            x-transition
            x-cloak
            class="absolute right-0 mt-2 w-48 overflow-hidden rounded-control border-border bg-surface py-1"
            style="box-shadow: var(--shadow-dropdown)"
            role="menu">
            <a href="{{ Route::has('settings.profile') ? route('settings.profile') : '#' }}"
                class="block px-4 py-2 text-sm text-text-soft hover:bg-surface-muted" role="menuitem">
                User Profile
            </a>
            @if (Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-danger hover:bg-danger-soft" role="menuitem">
                    Logout
                </button>
            </form>
            @endif
        </div>
    </div>
    @endauth
</header>