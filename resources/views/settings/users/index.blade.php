<x-layout.app title="Users">

    <x-layout.page-header
        title="Users"
        subtitle="Manage who can access this company's Fish Farm ERP."
        :breadcrumb="[['label' => 'Settings'], ['label' => 'Users']]">
        <x-slot:actions>
            @permission('users.create')
            <x-button.button :href="route('settings.users.create')" variant="secondary" icon="users">
                Create user
            </x-button.button>
            @endpermission
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Filters --}}
    <div class="mt-5">
        <x-card.card title="Search & filters" :padded="true">
            <form method="GET" action="{{ route('settings.users.index') }}"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.field label="Search" name="search">
                    <x-form.input name="search" :value="$search" placeholder="Name or email…" />
                </x-form.field>

                <x-form.field label="Status" name="status">
                    <x-form.select name="status" :selected="$status" placeholder="All statuses"
                        :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                </x-form.field>

                <x-form.field label="Role" name="role">
                    <x-form.select name="role" :selected="$roleId" placeholder="All roles"
                        :options="$roles->mapWithKeys(fn ($r) => [$r->id => $r->label])->all()" />
                </x-form.field>

                <div class="flex items-end gap-2">
                    <x-button.button type="submit" variant="primary" size="md">Apply</x-button.button>
                    @if ($search !== '' || $status !== '' || ! empty($roleId))
                    <x-button.button :href="route('settings.users.index')" variant="ghost" size="md">
                        Clear
                    </x-button.button>
                    @endif
                </div>
            </form>
        </x-card.card>
    </div>

    {{-- List --}}
    <div class="mt-5">
        <x-card.card :padded="false">
            <x-slot:title>All users</x-slot:title>
            <x-slot:actions>
                <x-badge.badge tone="info">{{ $users->total() }} total</x-badge.badge>
            </x-slot:actions>

            @if ($users->isEmpty())
            <x-empty-state.empty-state
                icon="users"
                title="No users found"
                message="Adjust your filters, or create the first user for this company.">
                <x-slot:actions>
                    @permission('users.create')
                    <x-button.button :href="route('settings.users.create')" variant="primary" size="sm">
                        Create user
                    </x-button.button>
                    @endpermission
                </x-slot:actions>
            </x-empty-state.empty-state>
            @else
            <x-table.table :headers="[
                    'Name',
                    'Email',
                    'Role',
                    ['label' => 'Status', 'align' => 'center'],
                    'Created',
                    ['label' => 'Actions', 'align' => 'right'],
                ]">
                @foreach ($users as $user)
                @php $role = $user->roles->first(); @endphp
                <tr>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="gradient-primary grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-bold text-white">
                                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                            </span>
                            <span class="font-medium text-text">{{ $user->name }}</span>
                            @if ($user->id === auth()->id())
                            <x-badge.badge tone="info">You</x-badge.badge>
                            @endif
                        </div>
                    </td>

                    <td class="px-4 py-3 text-muted">{{ $user->email }}</td>

                    <td class="px-4 py-3">
                        {{ $role?->label ?? '—' }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        @if ($user->is_active)
                        <x-badge.badge tone="success" dot>Active</x-badge.badge>
                        @else
                        <x-badge.badge tone="danger" dot>Inactive</x-badge.badge>
                        @endif
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap text-muted">
                        {{ $user->created_at?->format('d M Y') ?? '—' }}
                    </td>

                    <td class="px-4 py-3">
                        <div class="table-actions">
                            @permission('users.update')
                            <x-button.button :href="route('settings.users.edit', $user)"
                                variant="outline" size="sm">
                                Edit
                            </x-button.button>

                            @if ($user->id !== auth()->id())
                            <form method="POST" action="{{ route('settings.users.toggle-active', $user) }}"
                                data-confirm="{{ $user->is_active
                                                        ? 'Deactivate ' . $user->name . '? They will be signed out and unable to log in.'
                                                        : 'Activate ' . $user->name . '?' }}"
                                data-confirm-title="{{ $user->is_active ? 'Deactivate user' : 'Activate user' }}"
                                data-confirm-label="{{ $user->is_active ? 'Deactivate' : 'Activate' }}"
                                data-confirm-variant="{{ $user->is_active ? 'danger' : 'primary' }}">
                                @csrf
                                @method('PATCH')
                                <x-button.button type="submit" variant="ghost" size="sm">
                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                </x-button.button>
                            </form>
                            @endif
                            @endpermission

                            @permission('users.delete')
                            @if ($user->id !== auth()->id())
                            <form method="POST" action="{{ route('settings.users.destroy', $user) }}"
                                data-confirm="Delete {{ $user->name }}? This cannot be undone."
                                data-confirm-title="Delete user"
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

            <x-pagination.pagination :paginator="$users" />
            @endif
        </x-card.card>
    </div>

</x-layout.app>