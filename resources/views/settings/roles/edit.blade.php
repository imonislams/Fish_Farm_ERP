<x-layout.app title="Edit Role">

    <x-layout.page-header
        :title="'Edit — '.$role->label"
        subtitle="Update the role and its granted permissions."
        :breadcrumb="[['label' => 'Settings'], ['label' => 'Roles', 'route' => 'settings.roles.index'], ['label' => 'Edit']]" />

    <form method="POST" action="{{ route('settings.roles.update', $role) }}" class="mt-5 space-y-4">
        @csrf
        @method('PUT')

        <x-card.card title="Role details">
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.field label="Display label" name="label" required>
                        <x-form.input name="label" :value="old('label', $role->label)" required autofocus />
                    </x-form.field>

                    <x-form.field label="Machine name" name="name" required
                        :hint="$role->is_system
                                        ? 'System role — the machine name cannot be changed.'
                                        : 'Lowercase identifier used by the code.'">
                        <x-form.input name="name" :value="old('name', $role->name)"
                            :disabled="$role->is_system" required />
                        @if ($role->is_system)
                        {{-- Disabled inputs are not submitted: keep the value. --}}
                        <input type="hidden" name="name" value="{{ $role->name }}">
                        @endif
                    </x-form.field>
                </div>

                <x-form.field label="Description" name="description">
                    <x-form.textarea name="description" :rows="2" :value="old('description', $role->description)" />
                </x-form.field>
            </div>
        </x-card.card>

        <x-card.card title="Permissions"
            subtitle="Tick the capabilities this role grants. Groups can be toggled with “Select all”.">
            <x-form.permission-matrix :groups="$groups" :selected="$selected" />
        </x-card.card>

        <div class="flex-wrap items-center gap-2">
            <x-button.button type="submit" variant="primary">Save changes</x-button.button>
            <x-button.button :href="route('settings.roles.index')" variant="outline">Cancel</x-button.button>
        </div>
    </form>

    @if ($role->is_system)
    <div class="mt-5">
        <x-alert.alert tone="warning">
            This is a <strong>system role</strong>. Its machine name is fixed and it cannot be
            deleted, because the application references it by name. You may still adjust its
            permissions.
        </x-alert.alert>
    </div>
    @endif

</x-layout.app>