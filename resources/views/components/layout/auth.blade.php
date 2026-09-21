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

<body class="min-h-screen bg-background text-text">

    <div class="grid min-h-screen lg:grid-cols-2">

        {{-- Brand panel --}}
        <aside class="gradient-hero hidden flex-col justify-between p-10 text-white lg:flex">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-control bg-white/15 text-lg font-bold">F</span>
                <span class="text-lg font-semibold">{{ config('app.name') }}</span>
            </div>

            <div class="max-w-md">
                <h1 class="text-3xl font-bold leading-snug">
                    Manage your entire fish farm from one place.
                </h1>
                <p class="mt-3 text-sm text-white/75">
                    Ponds, fish stock, feed, FCR, sales, customers and accounts &mdash;
                    built with a real database, not spreadsheets.
                </p>
            </div>

            <p class="text-xs text-white/60">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </p>
        </aside>

        {{-- Form panel --}}
        <main class="flex items-center justify-center px-5 py-12">
            <div class="w-full max-w-sm">
                {{-- Compact brand for mobile --}}
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="gradient-primary grid h-11 w-11 place-items-center rounded-control text-lg font-bold text-white">F</span>
                    <span class="text-lg font-semibold">{{ config('app.name') }}</span>
                </div>

                <x-toast.toast-stack />

                {{ $slot }}
            </div>
        </main>
    </div>

    <x-layout.network-status />
</body>

</html>