<x-layout.auth title="Sign in">

    <h1 class="text-xl font-bold text-text">Sign in to your account</h1>
    <p class="mt-1 text-sm text-muted">
        Enter your credentials to access {{ $company?->name ?? config('app.name') }}.
    </p>

    {{-- General error (throttling, deactivated account) --}}
    @if ($errors->has('email') && ! $errors->has('email.required'))
    <div class="mt-5">
        <x-alert.alert tone="error">{{ $errors->first('email') }}</x-alert.alert>
    </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
        @csrf

        <x-form.field label="Email address" name="email" required>
            <x-form.input
                name="email"
                type="email"
                :value="old('email')"
                autocomplete="username"
                autofocus
                required
                placeholder="you@example.com" />
        </x-form.field>

        <x-form.field label="Password" name="password" required>
            <x-form.input
                name="password"
                type="password"
                autocomplete="current-password"
                required
                placeholder="••" />
        </x-form.field>

        <label class="flex items-center gap-2 text-sm text-text-soft">
            <input
                type="checkbox"
                name="remember"
                value="1"
                @checked(old('remember'))
                class="h-4 w-4 rounded border-border-strong text-primary focus:ring-primary/40">
            <span>Remember me on this device</span>
        </label>

        <x-button.button type="submit" variant="primary" class="w-full">
            Sign in
        </x-button.button>
    </form>

    <p class="mt-6 text-center text-xs text-muted">
        Access is restricted to authorised staff. Contact your administrator if you
        cannot sign in.
    </p>

</x-layout.auth>