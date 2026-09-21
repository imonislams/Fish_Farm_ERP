<x-layout.app title="Edit User">

    <x-layout.page-header
        :title="'Edit — '.$user->name"
        subtitle="Update this user's details, role and account status."
        :breadcrumb="[['label' => 'Settings'], ['label' => 'Users', 'route' => 'settings.users.index'], ['label' => 'Edit']]" />

    <div class="mt-5 grid-cols-1 gap-4 lg:grid-cols-3">

        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('settings.users.update', $user) }}">
                @csrf
                @method('PUT')

                <x-card.card title="Account details">
                    @include('settings.users._form', ['user' => $user])
                </x-card.card>

                <div class="mt-4 flex-wrap items-center gap-2">
                    <x-button.button type="submit" variant="primary">Save changes</x-button.button>
                    <x-button.button :href="route('settings.users.index')" variant="outline">Cancel</x-button.button>
                </div>
            </form>
        </div>

        {{-- Read-only summary --}}
        <div class="space-y-4">
            <x-card.card title="Account summary">
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
                        <dt class="text-muted">Current role</dt>
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
                        <dt class="text-muted">Created</dt>
                        <dd class="font-medium text-text">{{ $user->created_at?->format('d M Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-card.card>

            @if ($user->id === auth()->id())
            <x-alert.alert tone="warning">
                This is your own account. You cannot change your own role or deactivate
                yourself — ask another administrator to do that.
            </x-alert.alert>
            @endif

            @permission('users.delete')
            @if ($user->id !== auth()->id())
            <x-card.card title="Danger zone" tone="danger">
                <p class="text-sm text-muted">
                    Deleting a user permanently removes their account and role assignment.
                </p>

                <form method="POST" action="{{ route('settings.users.destroy', $user) }}"
                    class="mt-3"
                    data-confirm="Delete {{ $user->name }}? This cannot be undone."
                    data-confirm-title="Delete user"
                    data-confirm-variant="danger">
                    @csrf
                    @method('DELETE')
                    <x-button.button type="submit" variant="danger" size="sm">Delete user</x-button.button>
                </form>
            </x-card.card>
            @endif
            @endpermission
        </div>
    </div>

</x-layout.app>