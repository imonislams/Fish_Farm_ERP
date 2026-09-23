# ERP UI/UX — Fish Farm ERP

Read `docs/PROJECT.md` and `docs/ARCHITECTURE.md` first. This document describes the
**actual implemented** UI/UX system: the shell, the component library, the async
(no-full-reload) conventions, CSV export, and how to extend them.

---

## 1. Design principles

1. **The server renders; the browser enhances.** Blade + vanilla JS. No SPA, no
   React/Vue/Inertia/Livewire/Bootstrap (docs/PROJECT.md §3).
2. **One component, one look.** Every page uses the shared components in
   `resources/views/components/**`. Pages never hand-roll a button/table/modal.
3. **Honest data.** The UI never invents a figure. An unimplemented or empty area
   shows an empty state; a missing value shows `—`, never `0`
   (docs/BUSINESS_LOGIC.md §9).
4. **No unnecessary full-page reloads.** Simple actions (delete, export, filters)
   update only what changed (see §6).
5. **Server is authoritative.** Authorization, validation and business rules are
   enforced server-side. The UI is convenience only (docs/PERMISSIONS.md).

---

## 2. The application shell

`resources/views/components/layout/app.blade.php` — one layout for every page:

```
<x-layout.app title="…">  …  </x-layout.app>
```

```
┌───────────────┬──────────────────────────────────────────┐
│  Sidebar      │  Header (sticky)                         │
│  (dark green, ├──────────────────────────────────────────┤
│   scrollable, │  <main>  x-toast.toast-stack             │
│   collapsible)│          {{ $slot }}                     │
│               ├──────────────────────────────────────────┤
│  Logout       │  Footer                                  │
└───────────────┴──────────────────────────────────────────┘
 + x-confirm-modal.confirm-modal   (shared delete/confirm dialog)
 + x-layout.network-status          (PWA online/offline banner)
```

- **Sidebar** — `components/sidebar/sidebar.blade.php`. Menu comes from
  `config/navigation.php` (**never** hard-coded links). Active state uses
  `request()->routeIs()` with wildcard support so a group stays open across its
  children. Menu scrolls (`overflow-y-auto`) when it exceeds the viewport. On
  mobile it slides in over an overlay; `$store.sidebar` (Alpine) controls it.
  Entries are filtered by `permission` for **display only**.
- **Header** — `components/header/header.blade.php`. Mobile menu toggle, page
  title, live network indicator, notifications bell, user menu (profile/logout).
- The layout emits `<meta name="csrf-token">`, which the async layer reads (§6).

---

## 3. Reusable components

All under `resources/views/components/`. Add new ones here rather than styling a
page ad hoc — **never create a second version of an existing component.**

| Component                               | Tag                                                                           | Notes                                                                                 |
| --------------------------------------- | ----------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| Page header                             | `<x-layout.page-header>`                                                      | title, subtitle, `:breadcrumb="[[label, route?]]"`, `:actions` slot                   |
| Card                                    | `<x-card.card>`                                                               | `title`, `subtitle`, `:padded="false"` for tables                                     |
| Button                                  | `<x-button.button>`                                                           | variants: primary, secondary, outline, ghost, danger, success; `size`, `icon`, `href` |
| Badge                                   | `<x-badge.badge>`                                                             | tones: default, primary, success, warning, danger, info; `dot`                        |
| Table                                   | `<x-table.table>`                                                             | `:headers` (string or `['label','align']`); rows in the slot                          |
| Form field                              | `<x-form.field>`                                                              | label, required marker, hint, error display                                           |
| Input / Select / Textarea / Date picker | `<x-form.input>` `<x-form.select>` `<x-form.textarea>` `<x-form.date-picker>` | consistent sizing + error state                                                       |
| KPI card                                | `<x-kpi-card.kpi-card>`                                                       | dashboard/stat tile                                                                   |
| Empty state                             | `<x-empty-state.empty-state>`                                                 | icon, title, message, `:actions`                                                      |
| Pagination                              | `<x-pagination.pagination>`                                                   | Laravel paginator                                                                     |
| Alert                                   | `<x-alert.alert>`                                                             | static informational block                                                            |
| Toast stack                             | `<x-toast.toast-stack>`                                                       | rendered once in the layout                                                           |
| Confirm modal                           | `<x-confirm-modal.confirm-modal>`                                             | rendered once in the layout                                                           |
| Icon                                    | `<x-sidebar.icon name="…">`                                                   | inline SVG set; add icons **here**                                                    |

### Icons

`components/sidebar/icon.blade.php` holds the whole set as inline SVG (no icon
font, no CDN). Add a new icon there rather than pulling in a library.

---

## 4. Tables, forms, filters — the standard patterns

**List page shape** (ponds/fish/feed/ledger all follow it):

```
page-header (+ New / Export CSV actions)
→ filter card (search + selects + date range + Apply + Clear)
→ list card (title, total badge, table OR empty state, pagination)
```

- Filters are a **GET form** with `Apply` and a `Clear` link that appears only
  when a filter is set. All filtering happens **server-side**
  (`->when($filter, …)`) so the browser never downloads the whole table.
- Pagination uses `->withQueryString()` so filters survive page changes.
- Lists eager-load relationships (`->with([...])`) to avoid N+1.
- Every list renders `<x-empty-state>` rather than an empty `<tbody>`.
- Forms use `<x-form.field>` per input (label + hint + server error). Validation
  is server-side; the client adds nothing to it.

---

## 5. Formatting conventions

- **Currency** — `config('fishfarm.currency_symbol')` is used everywhere
  (`৳`). Never hard-code a currency symbol in a view.
- **Numbers** — `number_format()`; weights 3 decimals (kg), money 2.
- **Dates** — `d M Y` for display (`$model->date?->format('d M Y')`), `Y-m-d` in
  CSV. A missing date/number renders `—`.
- Formatting helpers live on the models (`sizeDisplay()`, `weightDisplay()`, …)
  so **no Blade file does arithmetic** (docs/ARCHITECTURE.md §3).

---

## 6. Async actions — "no full-page reload for every button"

The ERP stays server-rendered, but simple actions no longer reload the page.

**JS:** `resources/js/components/async-actions.js`
**Server:** `app/Support/AsyncResponse.php`

### How it works

A controller action returns one of:

```php
return AsyncResponse::ok($request, 'Pond deleted.', 'ponds.index');
return AsyncResponse::refuse($request, $e->getMessage());
```

`AsyncResponse` inspects the request:

| Request                                                 | Result                                         |
| ------------------------------------------------------- | ---------------------------------------------- |
| Normal (no JS)                                          | **redirect + flash** — behaviour is unchanged  |
| Async (`X-Requested-With` / `Accept: application/json`) | `200 {message, tone}` or `422 {message, tone}` |

So **every existing route keeps working** for non-JS clients, and the async layer
gets JSON. No business logic lives in `AsyncResponse`.

### Markup conventions

```blade
{{-- Explicit async, remove the row on success --}}
<form method="POST" action="…" data-async data-async-remove="closest-tr" data-async-busy="Deleting…">

{{-- Reload the page instead (when many figures change) --}}
<form method="POST" action="…" data-async data-async-reload>
```

**Automatic (no markup needed):** any form that has **`data-confirm` + `_method=DELETE`**
is treated as an async row-removal. That is why every delete button in the ERP
already removes its table row and shows a toast without a reload — the behaviour
is central, not per-page. Opt a specific form out with `data-no-async`.

### Flow of a delete

```
click Delete → shared confirm modal → fetch(POST) → 200 JSON
   → success toast → <tr> fades out and is removed
   (on a business refusal → 422 → error toast, page untouched)
```

### Toasts

`resources/js/components/toast.js` renders:

1. server flash messages (`<x-toast.toast-stack>`), and
2. async results — anything may emit:

```js
document.dispatchEvent(
    new CustomEvent("toast", {
        detail: { tone: "success", message: "Saved." },
    }),
);
```

### Loading states

`resources/js/components/loading-states.js` **automatically** disables the submit
button of any normal (non-async) form and shows a spinner + "Saving…", preventing
double submission. Override the label with `data-loading-label="…"`, skip with
`data-no-loading`.

---

## 7. CSV export

`app/Support/CsvExporter.php` — one implementation for the whole ERP.

```php
return CsvExporter::download('ponds', ['Pond No', 'Name'], $rows);
```

Conventions:

- Streamed (`streamDownload`), so a large export is never held in memory.
- **UTF-8 with BOM**, so Excel opens Bangla text and `৳` correctly.
- CRLF, quoted fields (RFC 4180).
- **Formula-injection protection:** a text cell starting with `= + - @` is
  prefixed with `'` so Excel cannot execute it.
- Dated filename: `ponds-2026-09-26.csv`.
- **Real rows only** — the same filters as the list, never sample data.

Export routes are read-only `GET`s on the module prefix (e.g. `ponds.export`,
`fish.stockings.export`) and carry the same `permission:` middleware as the list.
The view button reuses the current query string so an export matches what is on
screen:

```blade
<x-button.button :href="route('ponds.export', request()->query())" variant="outline" icon="download">
    Export CSV
</x-button.button>
```

Implemented exports: ponds, fish stockings, mortality, harvests, feed purchases,
feed usage. **To add one:** write an `export()` method on the list controller
(same filters as `index()`, map rows to an array), add a `GET …/export` route with
the list's permission, and add the button.

---

## 8. Responsive behaviour

- Content grid collapses at the `sm`/`lg` breakpoints (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`).
- Tables sit in `.table-shell` (horizontal scroll) so action buttons are never clipped.
- Sidebar is off-canvas below `lg`; the header exposes the toggle.
- `x-card.card` and modals fit small screens; page actions wrap rather than overflow.
- Action rows use `flex-wrap` so buttons never clip on narrow screens.

---

## 9. PWA

`public/manifest.webmanifest`, `public/sw.js`, `public/offline.html`,
`resources/js/pwa.js`, `resources/js/network.js`. Static assets are cached;
**authenticated responses and all non-GET requests are never cached**. See
docs/PWA.md. The UI never implies a write succeeded while offline.

---

## 10. Important frontend files

```
resources/css/app.css                     design tokens + component layers
resources/js/app.js                       bootstraps all modules
resources/js/components/ui.js             declarative helpers, collapse
resources/js/components/sidebar.js        sidebar store (Alpine)
resources/js/components/toast.js          toasts (flash + async)
resources/js/components/confirm-modal.js  shared confirmation dialog
resources/js/components/async-actions.js  fetch-based form actions  ← §6
resources/js/components/loading-states.js double-submit prevention   ← §6
resources/js/components/form-guards.js    progressive-enhancement form hints
resources/js/components/permission-matrix.js
resources/views/components/**             the component library (§3)
resources/views/components/layout/app.blade.php   the shell (§2)
config/navigation.php                     sidebar menu (route names only)
```

## 11. Important backend files

```
app/Support/AsyncResponse.php   redirect-or-JSON for every action  ← §6
app/Support/CsvExporter.php     the one CSV implementation          ← §7
app/Support/Metric.php          "no value yet" wrapper for the dashboard
app/View/Composers/CompanyComposer.php  shares branding with layouts
app/Http/Middleware/EnsurePermission.php  `permission:` route guard
bootstrap/app.php               middleware + alias registration
```

---

## 12. Extending the UI — checklist

1. Reuse existing components (see §3). Do not create a second version.
2. Use `route()` names, never raw URLs.
3. Put any figure through a **service**; Blade does not calculate.
4. Use `AsyncResponse::ok/refuse` in write actions.
5. Add node removal by giving the form `data-confirm` + `_method=DELETE` (auto),
   or `data-async data-async-remove="closest-tr"` explicitly.
6. Give list pages an `export()` + `GET …/export` route if a CSV is meaningful.
7. Keep every new route behind `permission:` middleware.
8. Update `docs/MODULES.md`, `docs/ROUTES.md` and `docs/CHANGELOG.md`.

## 13. Verification

- `php artisan view:cache` — all Blade templates compile.
- `php artisan route:list` — no route errors.
- `npm run build` — the JS/CSS bundle builds.
- Exercise the page in a browser before calling it done.
