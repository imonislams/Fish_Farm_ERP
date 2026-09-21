<x-layout.app title="Create Role">

    <x-layout.page-header
        title="Create Role"
        subtitle="Define a role and choose exactly which permissions it grants."
        :breadcrumb="[['label' => 'Settings'], ['label' => 'Roles', 'route' => 'settings.roles.index'], ['label' => 'Create']]" />

    <form method="POST" action="{{ route('settings.roles.store') }}" class="mt-5 space-y-4">
        @csrf

        <x-card.card title="Role details">
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.field label="Display label" name="label" required
                        hint="Shown in the interface, e.g. “Pond Supervisor”.">
                        <x-form.input name="label" :value="old('label')" required autofocus />
                    </x-form.field>

                    <x-form.field label="Machine name" name="name" required
                        hint="Lowercase identifier used by the code, e.g. pond_supervisor.">
                        <x-form.input name="name" :value="old('name')" required placeholder="pond_supervisor" />
                    </x-form.field>
                </div>

                <x-form.field label="Description" name="description"
                    hint="Optional summary of what this role is for.">
                    <x-form.textarea name="description" :rows="2" :value="old('description')" />
                </x-form.field>
            </div>
        </x-card.card>

        <x-card.card title="Permissions"
            subtitle="Tick the capabilities this role grants. Groups can be toggled with “Select all”.">
            <x-form.permission-matrix :groups="$groups" :selected="$selected" />
        </x-card.card>

        <div class="flex-wrap items-center gap-2">
            <x-button.button type="submit" variant="primary">Create role</x-button.button>
            <x-button.button :href="route('settings.roles.index')" variant="outline">Cancel</x-button.button>
        </div>
    </form>

</x-layout.app>