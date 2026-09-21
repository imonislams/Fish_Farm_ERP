<x-layout.app title="Roles">

    <x-layout.page-header
        title="Roles"
        subtitle="Roles bundle permissions. Assign one role to each user."
        :breadcrumb="[['label' => 'Settings'], ['label' => 'Roles']]">
        <x-slot:actions>
            @permission('roles.create')
            <x-button.button :href="route('settings.roles.create')" variant="secondary" icon="users">
                Create role
            </x-button.button>
            @endpermission
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mt-5">
        <x-card.card :padded="false">
            <x-slot:title>All roles</x-slot:title>
            <x-slot:actions>
                <x-badge.badge tone="info">{{ $roles->count() }} roles</x-badge.badge>
            </x-slot:actions>

            <x-table.table :headers="[
                'Role',
                'Machine name',
                ['label' => 'Permissions', 'align' => 'center'],
                ['label' => 'Users', 'align' => 'center'],
                ['label' => 'Type', 'align' => 'center'],
                ['label' => 'Actions', 'align' => 'right'],
            ]">
                @foreach ($roles as $role)
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-medium text-text">{{ $role->label }}</p>
                        @if ($role->description)
                        <p class="mt-0.5 text-xs text-muted">{{ $role->description }}</p>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <code class="rounded bg-surface-muted px-1.5 py-0.5 text-xs text-text-soft">
                            {{ $role->name }}
                        </code>
                    </td>

                    <td class="px-4 py-3 text-center">
                        <x-badge.badge tone="primary">{{ $role->permissions_count }}</x-badge.badge>
                    </td>

                    <td class="px-4 py-3 text-center">
                        <x-badge.badge tone="default">{{ $role->users_count }}</x-badge.badge>
                    </td>

                    <td class="px-4 py-3 text-center">
                        @if ($role->is_system)
                        <x-badge.badge tone="warning">System</x-badge.badge>
                        @else
                        <x-badge.badge tone="default">Custom</x-badge.badge>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <div class="table-actions">
                            @permission('roles.update')
                            <x-button.button :href="route('settings.roles.edit', $role)"
                                variant="outline" size="sm">
                                Edit
                            </x-button.button>
                            @endpermission

                            @permission('roles.delete')
                            @if (! $role->is_system)
                            <form method="POST" action="{{ route('settings.roles.destroy', $role) }}"
                                data-confirm="Delete the role &quot;{{ $role->label }}&quot;? This cannot be undone."
                                data-confirm-title="Delete role"
                                data-confirm-variant="danger">
                                @csrf
                                @method('DELETE')
                                <x-button.button type="submit" variant="danger" size="sm">
                                    Delete
                                </x-button.button>
                            </form>
                            @endif
                            @endpermission
                        </div>
                    </td>
                </tr>
                @endforeach
            </x-table.table>
        </x-card.card>
    </div>

    <div class="mt-5">
        <x-alert.alert tone="info">
            <strong>System roles</strong> are seeded and protected — their machine name cannot be
            changed and they cannot be deleted, because the application references them by name.
            Create a <em>custom</em> role when you need a different permission mix.
        </x-alert.alert>
    </div>

</x-layout.app>