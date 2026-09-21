@props(['title' => 'Dashboard'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    {{-- PWA --}}
    <meta name="theme-color" content="#14532d">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

    <title>{{ $title }} &middot; {{ config('app.name') }}</title>

    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background text-text">

    <a href="#main-content" class="sr-only-focusable">Skip to main content</a>

    <div class="flex min-h-screen">

        {{-- Sidebar (dark green) --}}
        <x-sidebar.sidebar />

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col lg:pl-64">

            {{-- Top header --}}
            <x-header.header :title="$title" />

            <main id="main-content" class="flex-1 px-4 py-5 sm:px-6 lg:px-8">
                {{-- Session flash messages render as toasts --}}
                <x-toast.toast-stack />

                {{ $slot }}
            </main>

            <footer class="border-t border-border px-4 py-4 text-xs text-muted sm:px-6 lg:px-8">
                {{ config('app.name') }} &copy; {{ date('Y') }}
                <span class="mx-1">&middot;</span>
                {{ config('fishfarm.version', 'v0') }}
            </footer>
        </div>
    </div>

    {{-- Reusable confirmation modal (replaces window.confirm) --}}
    <x-confirm-modal.confirm-modal />

    {{-- Offline banner + network status awareness --}}
    <x-layout.network-status />
</body>
</html>
