<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <meta name="theme-color" content="#14532d">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ $page['props']['title'] ?? config('app.name') }} &middot; {{ config('app.name') }}</title>

    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">

    {{-- Single entry point: CSS tokens + the Inertia/React app. --}}
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    {{-- Inertia server-side bootstrap. --}}
    @inertiaHead
</head>

<body class="min-h-screen bg-background text-text">
    @inertia
</body>

</html>