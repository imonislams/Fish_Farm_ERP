<x-layout.app title="Permissions">

    <x-layout.page-header
        title="Permissions"
        subtitle="Every capability in the system, grouped by module."
        :breadcrumb="[['label' => 'Settings'], ['label' => 'Permissions']]">
        <x-slot:actions>
            <x-badge.badge tone="info">{{ $total }} permissions</x-badge.badge>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mt-5">
        <x-alert.alert tone="info">
            Permissions are defined in <code>config/permissions.php</code> and seeded — they are not
            created here, because a permission only means something once the application enforces it.
            To change what a role can do, edit that role on the
            <a href="{{ route('settings.roles.index') }}" class="font-medium underline">Roles</a> page.
        </x-alert.alert>
    </div>

    {{-- Quick jump between groups --}}
    <div class="mt-5 flex-wrap gap-2">
        @foreach ($groups as $groupName => $permissions)
        <a href="#group-{{ \Illuminate\Support\Str::slug($groupName) }}"
            class="rounded-full border-border bg-surface px-3 py-1 text-xs font-medium text-text-soft hover:bg-surface-muted">
            {{ $groupName }}
            <span class="ml-1 text-muted">{{ $permissions->count() }}</span>
        </a>
        @endforeach
    </div>

    <div class="mt-5 space-y-4">
        @foreach ($groups as $groupName => $permissions)
        <x-card.card :title="$groupName" :padded="false">
            <x-slot:actions>
                <x-badge.badge tone="default">{{ $permissions->count() }}</x-badge.badge>
            </x-slot:actions>

            <div id="group-{{ \Illuminate\Support\Str::slug($groupName) }}">
                <x-table.table :headers="['Permission', 'Label', ['label' => 'Granted to', 'align' => 'right']]">
                    @foreach ($permissions as $permission)
                    <tr>
                        <td class="px-4 py-3">
                            <code class="rounded bg-surface-muted px-1.5 py-0.5 text-xs text-text-soft">
                                {{ $permission->name }}
                            </code>
                        </td>

                        <td class="px-4 py-3 text-text-soft">{{ $permission->label }}</td>

                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-1">
                                @forelse ($permission->roles as $role)
                                <x-badge.badge tone="primary">{{ $role->label }}</x-badge.badge>
                                @empty
                                <span class="text-xs text-muted">Not granted to any role</span>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </x-table.table>
            </div>
        </x-card.card>
        @endforeach
    </div>

</x-layout.app>