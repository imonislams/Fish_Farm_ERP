{{--
 | Shared user form fields — rendered by both the create and edit pages so the
 | two never drift apart. Expects:
 |   $roles   collection of Role (id, name, label)
 |   $user    User|null  (null when creating)
 |
 | The role options come from the database — never hard-coded here.
--}}
@php
$user ??= null;
$isEdit = $user !== null;
$currentRoleId = old('role_id', $isEdit ? $user->roles->first()?->id : null);
@endphp

<div class="space-y-4">
    <x-form.field label="Full name" name="name" required>
        <x-form.input name="name" :value="old('name', $user?->name)" required autofocus />
    </x-form.field>

    <x-form.field label="Email address" name="email" required
        hint="Used to sign in. Must be unique.">
        <x-form.input name="email" type="email" :value="old('email', $user?->email)" required />
    </x-form.field>

    <x-form.field label="Role" name="role_id" required
        hint="Determines which parts of the system this user can access.">
        <x-form.select
            name="role_id"
            placeholder="Select a role…"
            :selected="$currentRoleId"
            :options="$roles->mapWithKeys(fn ($role) => [$role->id => $role->label])->all()" />
    </x-form.field>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.field
            label="Password"
            name="password"
            :required="! $isEdit"
            :hint="$isEdit ? 'Leave blank to keep the current password.' : 'Minimum 8 characters.'">
            <x-form.input name="password" type="password" autocomplete="new-password"
                :required="! $isEdit" />
        </x-form.field>

        <x-form.field label="Confirm password" name="password_confirmation"
            :required="! $isEdit">
            <x-form.input name="password_confirmation" type="password" autocomplete="new-password"
                :required="! $isEdit" />
        </x-form.field>
    </div>

    <x-form.field label="Account status" name="is_active">
        <label class="flex items-center gap-2 text-sm text-text-soft">
            <input type="hidden" name="is_active" value="0">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', $isEdit ? $user->is_active : true))
            @disabled($isEdit && $user->id === auth()->id())
            class="h-4 w-4 rounded border-border-strong text-primary focus:ring-primary/40"
            >
            <span>Active — the user can sign in</span>
        </label>

        @if ($isEdit && $user->id === auth()->id())
        <p class="text-xs text-muted">You cannot deactivate your own account.</p>
        @endif
    </x-form.field>
</div>