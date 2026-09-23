<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#14532d">

    <title>{{ $title ?? 'Sign in' }} &middot; {{ config('app.name') }}</title>

    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
// Brand identity comes from the ONE company source — never hard-coded.
$brandName = \App\Support\CompanyContext::name();
$brandLogo = \App\Support\CompanyContext::logoUrl();
$brandInitials = \App\Support\CompanyContext::initials();
@endphp

<body class="min-h-screen bg-background text-text">

    <div class="grid min-h-screen lg:grid-cols-2">

        {{-- Brand panel --}}
        <aside class="gradient-hero relative hidden flex-col justify-between overflow-hidden p-10 text-white lg:flex xl:p-14">

            {{-- Subtle decorative scale texture (pure CSS, non-interactive). --}}
            <div class="auth-brand-pattern" aria-hidden="true"></div>

            <div class="relative flex items-center gap-3">
                <x-auth.brand-mark :logo="$brandLogo" :initials="$brandInitials"
                    class="h-11 w-11 text-lg" />
                <span class="text-lg font-semibold">{{ $brandName }}</span>
            </div>

            <div class="relative max-w-md">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/60">
                    Fish Farm ERP
                </p>

                <h1 class="mt-3 text-3xl font-bold leading-snug xl:text-4xl">
                    Manage your farm.
                    <br>Track your production.
                    <br>Grow your business.
                </h1>

                <p class="mt-4 text-sm text-white/75">
                    Ponds, fish stock, feed, FCR, sales and accounts &mdash; one system,
                    built on a real database instead of spreadsheets.
                </p>

                <ul class="mt-8 space-y-3 text-sm text-white/85">
                    <li class="flex items-center gap-3">
                        <x-sidebar.icon name="droplet" class="h-4 w-4 shrink-0 text-white/70" />
                        <span>Pond &amp; water management</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <x-sidebar.icon name="chart" class="h-4 w-4 shrink-0 text-white/70" />
                        <span>Feed, FCR &amp; production analytics</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <x-sidebar.icon name="cart" class="h-4 w-4 shrink-0 text-white/70" />
                        <span>Sales, customers &amp; accounts</span>
                    </li>
                </ul>
            </div>

            <p class="relative text-xs text-white/60">
                &copy; {{ date('Y') }} {{ $brandName }}
            </p>
        </aside>

        {{-- Form panel --}}
        <main class="flex items-center justify-center px-5 py-10 sm:py-12">
            <div class="w-full max-w-[26rem]">
                {{-- Compact brand for mobile --}}
                <div class="mb-7 flex items-center gap-3 lg:hidden">
                    <x-auth.brand-mark :logo="$brandLogo" :initials="$brandInitials"
                        class="h-11 w-11 text-lg" gradient />
                    <div class="min-w-0">
                        <p class="truncate text-base font-semibold text-text">{{ $brandName }}</p>
                        <p class="text-xs text-muted">Fish Farm ERP</p>
                    </div>
                </div>

                <x-toast.toast-stack />

                <div class="surface-card p-6 sm:p-7">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    <x-layout.network-status />
</body>

</html>