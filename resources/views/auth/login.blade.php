<x-layout.auth title="Sign in">

    <div class="auth-enter">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Welcome back</h1>
        <p class="mt-1.5 text-sm text-muted">
            Sign in to continue to your Fish Farm ERP account.
        </p>

        @php
        // A normal credential error is shown once, under the email field, by
        // x-form.field. The banner is reserved for the two account-level messages
        // that have no field of their own: throttling and a deactivated account.
        $emailError = $errors->first('email');
        $accountError = ($emailError && (str_contains($emailError, 'Too many') || str_contains($emailError, 'deactivated')))
            ? $emailError
            : null;
        @endphp

        @if ($accountError)
        <div class="mt-5">
            <x-alert.alert tone="error">{{ $accountError }}</x-alert.alert>
        </div>
        @endif

        <form
            method="POST"
            action="{{ route('login.store') }}"
            class="mt-6 space-y-4"
            data-login-form
            novalidate>
            @csrf

            {{-- Email --}}
            <x-form.field label="Email address" name="email" required>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted"
                        aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 7l9 6 9-6M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                        </svg>
                    </span>
                    <x-form.input
                        name="email"
                        type="email"
                        :value="old('email')"
                        autocomplete="username"
                        autofocus
                        required
                        inputmode="email"
                        placeholder="Enter your email address"
                        class="pl-9" />
                </div>
            </x-form.field>

            {{-- Password --}}
            <x-form.field label="Password" name="password" required>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted"
                        aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7 11V8a5 5 0 0110 0v3M6 11h12a1 1 0 011 1v7a1 1 0 01-1 1H6a1 1 0 01-1-1v-7a1 1 0 011-1z" />
                        </svg>
                    </span>
                    <x-form.input
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        placeholder="Enter your password"
                        class="pl-9 pr-11"
                        data-password-input />

                    {{-- Show/hide toggle — frontend only; never changes authentication. --}}
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-control text-muted transition-colors hover:text-text-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                        data-password-toggle
                        aria-label="Show password"
                        aria-pressed="false">
                        <svg data-eye-show class="h-4 w-4" fill="none" stroke="currentColor"
                            stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg data-eye-hide class="hidden h-4 w-4" fill="none" stroke="currentColor"
                            stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3l18 18M10.6 6.1A9.7 9.7 0 0112 6c6 0 9.5 6 9.5 6a17 17 0 01-3.2 3.8M6.4 7.4A17 17 0 002.5 13s3.5 6 9.5 6a9.6 9.6 0 003.5-.65" />
                        </svg>
                    </button>
                </div>
            </x-form.field>

            <div class="flex items-center justify-between gap-3">
                <label class="group flex cursor-pointer items-center gap-2 text-sm text-text-soft">
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        @checked(old('remember'))
                        class="h-4 w-4 shrink-0 cursor-pointer rounded border-border-strong text-primary accent-primary focus:ring-2 focus:ring-primary/40 focus:ring-offset-0">
                    <span class="select-none group-hover:text-text">Remember me</span>
                </label>
            </div>

            <x-button.button
                type="submit"
                variant="primary"
                class="w-full !py-2.5"
                data-login-submit>
                <span data-login-label>Sign in</span>
            </x-button.button>
        </form>

        <p class="mt-6 flex items-start gap-2 text-xs text-muted">
            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"
                viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 3l7 3v5c0 4.5-3 8.3-7 9.5C8 19.3 5 15.5 5 11V6l7-3z" />
            </svg>
            <span>Access is restricted to authorised staff. Contact your administrator
                if you cannot sign in.</span>
        </p>
    </div>

</x-layout.auth>