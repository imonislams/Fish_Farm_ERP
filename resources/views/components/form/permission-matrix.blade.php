{{--
 | Permission matrix — grouped, accessible checkboxes.
 |
 | Used by BOTH the role create and role edit pages so the two never drift.
 | Permissions come from the database, grouped by module, so the list stays
 | readable instead of being one giant unorganised block.
 |
 | Expects:
 |   $groups    Collection of ['group' => string, 'permissions' => Collection]
 |   $selected  array of permission NAMES currently granted
 |
 | Each group has a "select all" toggle driven by vanilla JS
 | (resources/js/components/permission-matrix.js).
--}}
@props(['groups' => collect(), 'selected' => []])

@php
$selected = array_map('strval', (array) $selected);
@endphp

<div class="space-y-4" data-permission-matrix>
    @foreach ($groups as $group)
    <section class="surface-card overflow-hidden">
        <header class="flex flex-wrap items-center justify-between gap-2 border-b border-border bg-surface-muted px-4 py-2.5">
            <h3 class="text-sm font-semibold text-text">{{ $group['group'] }}</h3>

            <label class="flex cursor-pointer items-center gap-2 text-xs text-muted">
                <input
                    type="checkbox"
                    data-group-toggle
                    class="h-4 w-4 rounded border-border-strong text-primary focus:ring-primary/40">
                <span>Select all</span>
            </label>
        </header>

        <div class="grid grid-cols-1 gap-x-4 gap-y-2.5 p-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($group['permissions'] as $permission)
            <label class="flex items-start gap-2 text-sm">
                <input
                    type="checkbox"
                    name="permissions[]"
                    value="{{ $permission->name }}"
                    data-permission
                    @checked(in_array((string) $permission->name, $selected, true))
                class="mt-0.5 h-4 w-4 shrink-0 rounded border-border-strong text-primary focus:ring-primary/40"
                >
                <span class="min-w-0">
                    <span class="block text-text-soft">{{ $permission->label }}</span>
                    <code class="block truncate text-xs text-muted">{{ $permission->name }}</code>
                </span>
            </label>
            @endforeach
        </div>
    </section>
    @endforeach

    @error('permissions')
    <p class="text-xs text-danger" role="alert">{{ $message }}</p>
    @enderror
</div>