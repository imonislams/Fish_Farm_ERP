<x-layout.app title="Company Settings">

    <x-layout.page-header
        title="Company Settings"
        subtitle="The single business identity used throughout the system."
        :breadcrumb="[['label' => 'Settings'], ['label' => 'Company']]" />

    <div class="mt-5 grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Form --}}
        <div class="lg:col-span-2">
            <form
                method="POST"
                action="{{ route('settings.company.update') }}"
                enctype="multipart/form-data"
                class="space-y-4">
                @csrf
                @method('PUT')

                <x-card.card title="Business identity" subtitle="Name, contact details and locale.">
                    <div class="space-y-4">
                        <x-form.field label="Company / Farm Name" name="name" required
                            hint="Shown in the sidebar, header and login screen.">
                            <x-form.input name="name" :value="old('name', $company->name)" required autofocus />
                        </x-form.field>

                        <x-form.field label="Short Code" name="code"
                            hint="Optional unique code, e.g. FISHFARM.">
                            <x-form.input name="code" :value="old('code', $company->code)" />
                        </x-form.field>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-form.field label="Phone" name="phone">
                                <x-form.input name="phone" :value="old('phone', $company->phone)" />
                            </x-form.field>

                            <x-form.field label="Email" name="email">
                                <x-form.input name="email" type="email" :value="old('email', $company->email)" />
                            </x-form.field>
                        </div>

                        <x-form.field label="Address" name="address">
                            <x-form.textarea name="address" :rows="3" :value="old('address', $company->address)" />
                        </x-form.field>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-form.field label="Currency" name="currency" required>
                                <x-form.select name="currency" :options="$currencies"
                                    :selected="old('currency', $company->currency)" />
                            </x-form.field>

                            <x-form.field label="Timezone" name="timezone" required>
                                <x-form.select name="timezone" :options="$timezones"
                                    :selected="old('timezone', $company->timezone)" />
                            </x-form.field>
                        </div>

                        <x-form.field label="Status" name="status" required
                            hint="Set to inactive to mark the company disabled. It is never deleted.">
                            <x-form.select name="status" :selected="old('status', $company->status)"
                                :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                        </x-form.field>
                    </div>
                </x-card.card>

                <div class="flex flex-wrap items-center gap-2">
                    <x-button.button type="submit" variant="primary">Save changes</x-button.button>
                    <x-button.button :href="route('dashboard')" variant="outline">Cancel</x-button.button>
                </div>
            </form>
        </div>

        {{-- Logo + summary --}}
        <div class="space-y-4">
            <x-card.card title="Logo">
                <form method="POST" action="{{ route('settings.company.update') }}"
                    enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    @method('PUT')

                    {{-- Keep the other fields intact when only the logo changes. --}}
                    <input type="hidden" name="name" value="{{ $company->name }}">
                    <input type="hidden" name="code" value="{{ $company->code }}">
                    <input type="hidden" name="phone" value="{{ $company->phone }}">
                    <input type="hidden" name="email" value="{{ $company->email }}">
                    <input type="hidden" name="address" value="{{ $company->address }}">
                    <input type="hidden" name="currency" value="{{ $company->currency }}">
                    <input type="hidden" name="timezone" value="{{ $company->timezone }}">
                    <input type="hidden" name="status" value="{{ $company->status }}">

                    <div class="flex items-center gap-3">
                        @if ($company->logo)
                        <img src="{{ asset('storage/'.$company->logo) }}" alt="{{ $company->name }}"
                            class="h-14 w-14 rounded-control border-border object-cover">
                        @else
                        <span class="gradient-primary grid h-14 w-14 shrink-0 place-items-center rounded-control text-lg font-bold text-white">
                            {{ strtoupper(mb_substr($company->name, 0, 2)) }}
                        </span>
                        @endif

                        <div class="min-w-0 text-sm">
                            <p class="font-medium text-text">{{ $company->name }}</p>
                            <p class="text-xs text-muted">
                                {{ $company->logo ? 'Custom logo uploaded' : 'No logo — initials are shown' }}
                            </p>
                        </div>
                    </div>

                    <x-form.field label="Upload logo" name="logo" hint="PNG, JPG, WEBP or SVG. Max 2 MB.">
                        <x-form.input name="logo" type="file" accept="image/*" />
                    </x-form.field>

                    @if ($company->logo)
                    <label class="flex items-center gap-2 text-sm text-danger">
                        <input type="checkbox" name="remove_logo" value="1"
                            class="h-4 w-4 rounded border-border-strong text-danger focus:ring-danger/40">
                        <span>Remove the current logo</span>
                    </label>
                    @endif

                    <x-button.button type="submit" variant="outline" size="sm" class="w-full">
                        Update logo
                    </x-button.button>
                </form>
            </x-card.card>

            <x-card.card title="About this record">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Record ID</dt>
                        <dd class="font-medium text-text">#{{ $company->id }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Created</dt>
                        <dd class="font-medium text-text">{{ $company->created_at?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Last updated</dt>
                        <dd class="font-medium text-text">{{ $company->updated_at?->diffForHumans() ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="mt-4">
                    <x-alert.alert tone="info">
                        Version 1 runs as a <strong>single company</strong>. Multiple users work
                        inside this one farm — there is no multi-tenant switching.
                    </x-alert.alert>
                </div>
            </x-card.card>
        </div>
    </div>

</x-layout.app>