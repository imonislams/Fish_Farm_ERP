# UI Guidelines — Fish Farm ERP

The visual system is defined once in `resources/css/app.css` and consumed through
reusable Blade components. **Do not invent per-page colours, gradients or
spacing.** Read `docs/PROJECT.md` first.

---

## 1. Design direction

Professional ERP: clean, fast, consistent, responsive, data-focused.
Dark green sidebar · green/teal branding · white content area · gradient hero
bands · modern KPI cards · soft shadows · rounded corners · clean tables ·
Bengali-friendly typography.

Avoid: excessive animation, decorative gradients, over-rounded elements,
unnecessary popups, icon overload, visual clutter.

## 2. Colour system

Defined as Tailwind v4 `@theme` tokens in `resources/css/app.css`. Use the
generated utilities (`bg-primary`, `text-muted`, `border-border`, …) — never raw
hex values in Blade.

### Brand
| Token                    | Value     | Use                          |
| ------------------------ | --------- | ---------------------------- |
| `--color-primary`        | `#15803d` | primary actions, active nav  |
| `--color-primary-dark`   | `#14532d` | gradient start, headings     |
| `--color-primary-light`  | `#22c55e` | accents                       |
| `--color-secondary`      | `#0f766e` | teal accent                  |
| `--color-secondary-dark` | `#115e59` | hero gradient end            |
| `--color-accent`         | `#14b8a6` | highlights                   |

### Semantic
| Token                  | Value     | Use                    |
| ---------------------- | --------- | ---------------------- |
| `--color-success`      | `#16a34a` | success states, gains  |
| `--color-warning`      | `#d97706` | warnings, pending      |
| `--color-danger`       | `#dc2626` | errors, losses, delete |
| `--color-info`         | `#0284c7` | neutral information    |

Soft variants (`--color-success-soft`, `--color-danger-soft`, …) are for badge
and alert backgrounds.

### Surfaces & text
`--color-background` `#f1f5f4` · `--color-surface` `#ffffff` ·
`--color-surface-muted` `#f8fafc` · `--color-border` `#e2e8f0` ·
`--color-text` `#0f172a` · `--color-text-soft` `#334155` ·
`--color-muted` `#64748b`.

### Sidebar
`--color-sidebar` `#0b2f1d` · `--color-sidebar-hover` `#14532d` ·
`--color-sidebar-active` `#16a34a` · `--color-sidebar-text` `#d1fae5` ·
`--color-sidebar-muted` `#86b8a0`.

## 3. Gradients — exactly two

1. **`.gradient-primary`** — `dark green → green → teal` (120°).
   Primary buttons, brand marks.
2. **`.gradient-hero`** — `deep green → teal` (135°).
   Sidebar background, page-header bands, auth brand panel.

Adding a third gradient requires a design decision recorded in
`docs/CHANGELOG.md`. Gradients are part of the identity, not decoration.

## 4. Radii, shadows, spacing

| Token                  | Value      | Use                     |
| ---------------------- | ---------- | ----------------------- |
| `--radius-card`        | `0.875rem` | cards, panels, heroes   |
| `--radius-control`     | `0.5rem`   | buttons, inputs, badges |
| `--shadow-card`        | soft       | cards at rest           |
| `--shadow-card-hover`  | deeper     | hoverable cards         |
| `--shadow-dropdown`    | strong     | dropdowns, popovers     |

Spacing uses Tailwind's scale. Standard rhythm: `gap-4` inside grids,
`mt-5` between page sections, `p-4` inside cards.

## 5. Typography

Font stack: `Inter`, `Hind Siliguri`, system fallbacks, `Noto Sans Bengali`.
The Bengali-capable fonts are required — the ERP must render Bangla text
correctly without layout shifts. Base line-height is relaxed (1.6) for
mixed-script content.

Scale: page title `text-xl`/`text-2xl` · card title `text-sm font-semibold` ·
body `text-sm` · meta/hint `text-xs text-muted`.

## 6. Component library

All reusable UI lives in `resources/views/components/` and is invoked with
`<x-folder.component>`. Reuse these instead of rewriting HTML.

| Component                    | Purpose                                     |
| ---------------------------- | ------------------------------------------- |
| `x-layout.app`               | main application shell (sidebar + header)   |
| `x-layout.auth`              | split-screen auth shell                     |
| `x-layout.error`             | centred error card                          |
| `x-layout.page-header`       | gradient hero with title + actions slot     |
| `x-layout.network-indicator` | Online/Offline pill                         |
| `x-layout.network-status`    | offline banner + reconnect notice           |
| `x-sidebar.sidebar`          | navigation (driven by config/navigation.php)|
| `x-sidebar.icon`             | inline SVG icon set                         |
| `x-header.header`            | sticky header with user menu                |
| `x-breadcrumb.breadcrumb`    | breadcrumb trail                            |
| `x-card.card`                | panel with optional title + actions         |
| `x-kpi-card.kpi-card`        | dashboard metric tile                       |
| `x-table.table`              | scroll-safe table shell                     |
| `x-pagination.pagination`    | paginator UI                                |
| `x-button.button`            | button / link button                        |
| `x-badge.badge`              | status pill                                 |
| `x-alert.alert`              | inline alert                                |
| `x-toast.toast-stack`        | flash-message toasts                        |
| `x-confirm-modal.confirm-modal` | confirmation dialog (replaces `confirm()`) |
| `x-form.field`               | label + hint + error wrapper                |
| `x-form.input` / `select` / `textarea` / `date-picker` | form controls    |
| `x-empty-state.empty-state`  | no-records state                            |
| `x-loading.loading`          | spinner + label                             |

### Usage examples

```blade
<x-layout.page-header title="All Ponds" subtitle="..." :breadcrumb="[
    ['label' => 'Ponds', 'route' => 'ponds.index'],
    ['label' => 'New Pond'],
]">
    <x-slot:actions>
        <x-button.button :href="route('ponds.create')" icon="droplet">New Pond</x-button.button>
    </x-slot:actions>
</x-layout.page-header>

<x-card.card title="Pond Status">
    @forelse ($ponds as $pond)
        ...
    @empty
        <x-empty-state.empty-state title="No ponds yet" message="Create your first pond to get started." />
    @endforelse
</x-card.card>
```

## 7. Navigation rules

- The sidebar menu is defined **only** in `config/navigation.php` — never
  hard-code links in the Blade file.
- Menu entries use **route names**, not URLs.
- Groups are expandable and stay open on all their child pages (active-state via
  `request()->routeIs()`).
- The active item is highlighted with `bg-sidebar-active`.
- The sidebar is fixed on desktop (`lg:`) and an off-canvas drawer on mobile.
- Hiding a menu item is **not** authorization. See `docs/PERMISSIONS.md`.

## 8. Responsive design

Must work on desktop, laptop, tablet and mobile.

- **Actions never clip.** Use `flex-wrap` / `.table-actions` inside a
  `.table-shell` so buttons wrap instead of being cut off.
- **Tables** live inside `.table-shell` (`overflow-x: auto`) so wide tables
  scroll horizontally rather than breaking layout.
- **Forms** stack vertically on mobile (`space-y-1.5` inside `x-form.field`).
- **Grids** collapse: `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5`.
  Always include the base `grid` class with the column classes.
- **Sidebar** becomes a drawer under `lg`, closed by overlay click or Escape.

## 9. Alerts, toasts and confirmations

- **Toasts** — transient flash feedback. Set `session('success'|'error'|'warning'|'info')`
  in the controller; `<x-toast.toast-stack />` renders them and they
  auto-dismiss (errors/warnings linger longer).
- **Alerts** — persistent in-page messages.
- **Confirmations** — **never** use the native `confirm()` for destructive
  actions. Add `data-confirm` to the form; the shared modal handles it:

```blade
<form method="POST" action="{{ route('ponds.destroy', $pond) }}"
      data-confirm="Delete this pond? This cannot be undone."
      data-confirm-title="Delete pond"
      data-confirm-variant="danger">
    @csrf @method('DELETE')
    <x-button.button type="submit" variant="danger" size="sm">Delete</x-button.button>
</form>
```

## 10. Empty and loading states

- Every list/report renders `<x-empty-state.empty-state>` instead of an empty
  table body.
- Long-running sections use `<x-loading.loading>`.
- **Never** fill an empty area with placeholder business numbers. An
  unimplemented module shows `x-layout.module-pending` / the placeholder page.

## 11. Forms

Build forms from `x-form.field` + `x-form.input` / `select` / `textarea` /
`date-picker`. `x-form.field` renders the label, required marker, hint and the
Laravel validation error for the field automatically.

```blade
<x-form.field label="Pond name" name="name" required>
    <x-form.input name="name" :value="old('name')" />
</x-form.field>
```

Server-side validation is authoritative; client-side validation is a convenience
only (see `resources/js/components/form-guards.js`).

## 12. JavaScript

Vanilla JS + Vite only. Layered as `app.js` (bootstrap) → `network.js`,
`pwa.js`, `components/*.js`. UI interactions only — no business logic.

Declarative bindings (`x-data`, `x-show`, `:class`, `@click`) are supported by a
tiny local helper in `resources/js/components/ui.js`; there is **no** Alpine
dependency. Sidebar state is exposed as `window.$store.sidebar`.

## 13. Accessibility

Keyboard-accessible controls, visible `:focus-visible` ring, `aria-current` on
the active nav item, `aria-expanded` on expandable groups, `role="status"` on
the network indicator, a skip-to-content link, and labels tied to inputs via
`for`/`id`.

## 14. Adding UI — checklist

1. Check the component table above — reuse before creating.
2. Use design tokens; no raw hex or ad-hoc gradients.
3. Use route names; no hard-coded URLs.
4. Include `grid` with any `grid-cols-*` classes.
5. Wrap tables in `.table-shell`; wrap actions in `.table-actions`.
6. Provide an empty state for every list.
7. Test at 375px width — actions must remain clickable.
8. If a genuinely new pattern is needed, add it to the component library **and**
   to the table above.