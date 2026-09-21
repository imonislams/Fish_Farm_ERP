<x-layout.app title="Create User">

    <x-layout.page-header
        title="Create User"
        subtitle="Add a new user to the company."
        :breadcrumb="[['label' => 'Settings'], ['label' => 'Users', 'route' => 'settings.users.index'], ['label' => 'Create']]" />

    <div class="mt-5 max-w-2xl">
        <form method="POST" action="{{ route('settings.users.store') }}">
            @csrf

            <x-card.card title="Account details">
                @include('settings.users._form', ['user' => null])
            </x-card.card>

            <div class="mt-4 flex-wrap items-center gap-2">
                <x-button.button type="submit" variant="primary">Create user</x-button.button>
                <x-button.button :href="route('settings.users.index')" variant="outline">Cancel</x-button.button>
            </div>
        </form>
    </div>

</x-layout.app>