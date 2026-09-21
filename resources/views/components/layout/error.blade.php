<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14532d">
    <title>{{ $title ?? 'Error' }} &middot; {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="grid min-h-screen place-items-center bg-background px-5 text-text">
    <div class="surface-card w-full max-w-md p-8 text-center">
        <div class="gradient-primary mx-auto grid h-12 w-12 place-items-center rounded-control text-lg font-bold text-white">
            !
        </div>
        <h1 class="mt-4 text-2xl font-bold">{{ $code ?? 'Error' }}</h1>
        <p class="mt-2 text-sm text-muted">{{ $message ?? 'Something went wrong.' }}</p>

        <div class="mt-6 flex justify-center">
            <x-button.button :href="url('/')" variant="primary">Back to dashboard</x-button.button>
        </div>
    </div>
</body>

</html>