<x-layout.app title="User Profile">

    <x-layout.page-header
        title="User Profile"
        subtitle="Manage your own account details."
        :breadcrumb="[['label' => 'Settings'], ['label' => 'User Profile']]" />

    <div class="mt-5 grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Editable details --}}
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('settings.profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <x-card.card title="Account details">
                    <div class="space-y-4">
                        <x-form.field label="Full name" name="name" required>
                            <x-form.input name="name" :value="old('name', $user->name)" required />
                        </x-form.field>

                        <x-form.field label="Email address" name="email" required
                            hint="Used to sign in. Must be unique.">
                            <x-form.input name="email" type="email" :value="old('email', $user->email)" required />
                        </x-form.field>
                    </div>
                </x-card.card>

                <x-card.card title="Change password"
                    subtitle="Leave these blank to keep your current password.">
                    <div class="space-y-4">
                        <x-form.field label="Current password" name="current_password"
                            hint="Required only when setting a new password.">
                            <x-form.input name="current_password" type="password"
                                autocomplete="current-password" />
                        </x-form.field>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-form.field label="New password" name="password"
                                hint="Minimum 8 characters.">
                                <x-form.input name="password" type="password" autocomplete="new-password" />
                            </x-form.field>

                            <x-form.field label="Confirm new password" name="password_confirmation">
                                <x-form.input name="password_confirmation" type="password"
                                    autocomplete="new-password" />
                            </x-form.field>
                        </div>
                    </div>
                </x-card.card>

                <div class="flex-wrap items-center gap-2">
                    <x-button.button type="submit" variant="primary">Save changes</x-button.button>
                    <x-button.button :href="route('dashboard')" variant="outline">Cancel</x-button.button>
                </div>
            </form>
        </div>

        {{-- Read-only account info --}}
        <div class="space-y-4">
            <x-card.card title="Your account">
                <div class="flex items-center gap-3">
                    <span class="gradient-primary grid h-12 w-12 shrink-0 place-items-center rounded-full text-base font-bold text-white">
                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-medium text-text">{{ $user->name }}</p>
                        <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                    </div>
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Role</dt>
                        <dd class="font-medium text-text">
                            {{ $user->roles->first()?->label ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Status</dt>
                        <dd>
                            @if ($user->is_active)
                            <x-badge.badge tone="success" dot>Active</x-badge.badge>
                            @else
                            <x-badge.badge tone="danger" dot>Inactive</x-badge.badge>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Company</dt>
                        <dd class="font-medium text-text">{{ $user->company?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Member since</dt>
                        <dd class="font-medium text-text">{{ $user->created_at?->format('d M Y') ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="mt-4">
                    <x-alert.alert tone="info">
                        Your <strong>role</strong>, account status and company can only be changed by
                        an administrator. You cannot modify your own access level.
                    </x-alert.alert>
                </div>
            </x-card.card>

            {{-- A user's own capabilities, read-only --}}
            <x-card.card title="Your permissions"
                :subtitle="$user->roles->first()?->label
                            ? 'Granted through the “'.$user->roles->first()->label.'” role.'
                            : 'You have no role assigned.'">
                @php $permissions = $user->permissionNames(); @endphp

                @if ($permissions->isEmpty())
                <p class="text-sm text-muted">No permissions granted.</p>
                @else
                <div class="flex flex-wrap gap-1">
                    @foreach ($permissions as $permission)
                    <code class="rounded bg-surface-muted px-1.5 py-0.5 text-xs text-text-soft">
                        {{ $permission }}
                    </code>
                    @endforeach
                </div>
                @endif
            </x-card.card>
        </div>
    </div>

</x-layout.app>