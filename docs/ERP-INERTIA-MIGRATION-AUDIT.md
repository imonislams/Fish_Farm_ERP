# ERP Inertia Migration — Audit

Status: **audit complete — Inertia + React migration IN PROGRESS under the new brief.**
This file supersedes the earlier "blocked" audit: the frozen prompt is an explicit
architectural decision by the project owner to move the frontend to **Laravel + Inertia +
React**, and the environment now supports it (Node/npm available).

---

## 1. Environment (verified, not inferred)

| Check | Command / file | Result |
| --- | --- | --- |
| Laravel | `php artisan --version` | **12.69.2** |
| PHP | `C:\xampp\php\php.exe -v` | **8.2.12** (XAMPP, ZTS) |
| Node | `C:\Program Files\nodejs\node.exe -v` | **v25.2.1** |
| npm | `C:\Program Files\nodejs\npm.cmd -v` | **11.6.2** |
| Database | `.env` | MySQL `fish_farm_erp` @127.0.0.1 |
| Inertia (PHP) | `vendor/inertiajs` | **absent — to be installed** |
| React / Vue | `package.json` | **neither — to be installed (React)** |
| Frontend entry | `resources/js/app.js` | vanilla JS bootstrap (12 modules) |
| Views | `resources/views/**/*.blade.php` | **~148 Blade views** |
| Root layout | `resources/views/components/layout/app.blade.php` | single Blade shell |
| Build | `vite.config.js` | laravel-vite-plugin + `@tailwindcss/vite` (Tailwind 4) |
| Built assets | `public/build` | present (manifest + assets) |

### Live data present (do NOT wipe)

`Sales: 0 · Ponds: 6 · Users: 2 · FeedUsage: 4 · GrowthRecord: 7 · Customers: 0 · Suppliers: 0 · ExpenseEntry: 0 · IncomeEntry: 0`

The database is real and is **preserved**. No `migrate:fresh` / `refresh` / `db:wipe`
will be run at any point.

---

## 2. Current architecture (before change)

```
Browser
   │
   ▼
Laravel Route (routes/web.php, routes/auth.php)
   │
   ▼
Middleware: auth → permission:<key> (EnsurePermission) → SubstituteBindings
   │
   ▼
Controller (app/Http/Controllers/**)
   │   Gate::authorize(...) re-asserts policies per record
   ▼
Service / Business logic (app/Services/**)
   │
   ▼
Model (app/Models/**) → MySQL / MariaDB
   │
   ▼
Blade view (resources/views/**, ~148 files)
   │
   ▼
@vite → resources/js (vanilla modules) + resources/css/app.css (Tailwind 4)
```

Classic **server-rendered Laravel + Blade + Tailwind 4 + vanilla JS** app. No SPA layer, no
client router, no Inertia middleware.

### Existing reusable Blade UI (good — will be mirrored in React)

`app.blade.php` composes: layout shell (`components/layout/app`), sidebar
(`components/sidebar/sidebar`), header (`components/header/header`), page-header,
breadcrumb, card, table, form fields (input/select/textarea/date-picker/field),
kpi-card, badge, alert, button, empty-state, loading, toast-stack, confirm-modal.

### Existing client behaviour (good)

`resources/js/app.js` boots: sidebar collapse, toasts (`toast` CustomEvent + flash),
shared confirm modal, async actions, loading states, form guards, permission matrix,
network status, PWA. Many of these already satisfy the brief's UX asks in vanilla JS.

### Route inventory (modules — from `routes/web.php` + `config/navigation.php`)

| Module | Prefix | Notable routes |
| --- | --- | --- |
| Dashboard | `/dashboard` | `dashboard` |
| Ponds | `/fish-farm/ponds` | index, create, store, show, edit, update, destroy, export, types.*, status |
| Pond Ledger | `/fish-farm/pond-ledger` | index, stocking, mortality, transfers, transactions(+create/store/destroy) |
| Feed | `/fish-farm/feed` | index, stock, types.*, purchases.*(+export), usages.*(+export), adjustments.* |
| FCR & Growth | `/fish-farm/fcr` | index, feed-growth, comparison, reports, inspections.*, growth.*, schedules.* |
| Fish Stock | `/fish-farm/fish` | index, species.*, stockings.*(+export), mortalities.*(+export), harvests.*(+export) |
| Sales | `/fish-farm/sales` | index(dashboard), list, export, ledger(+export), show, create, store, edit, update, destroy |
| Customers | `/fish-farm/customers` | index(+export), dues(+export), payments.*(+export), create, store, edit, update, destroy |
| Suppliers | `/fish-farm/suppliers` | index(+export), purchases.*(+export), payments.*(+export), dues(+export), create…destroy |
| Parties | `/fish-farm/parties` | index(+export), transactions(+export), ledger(+export), create…destroy |
| Finance | `/fish-farm/finance` | income.*(+export), expenses.*(+export), categories.*, profit-loss(+export) |
| Reports | `/fish-farm/reports` | index, sales, purchases, feed, fish-stock, ponds, fcr-growth, income, expenses, profit-loss, `{report}/export` |
| Settings | `/settings` | company, profile, users.*, roles.*, permissions |

### Auth & Authorization (preserve exactly)

* Session auth via `LoginController` (no Breeze/Jetstream/Fortify). CSRF + throttle.
* Every protected route carries `permission:<key>` middleware (`EnsurePermission`).
* Per-record checks via **Policies** registered in `AppServiceProvider` + `Gate::authorize()`.
* `Gate::before` Super-Admin bypass (`super_admin` role).
* Blade `@permission` / `@role` directives = display only.

**Migration rule: React visibility is UX only. Laravel continues to enforce every check.**

---

## 3. DB schema changes

**None.** No migrations are added or run. The brief allows a safe migration only if
necessary; none is necessary — the Inertia layer needs no schema.

---

## 4. Confirmed bugs (investigated with evidence)

### BUG #1 — report pages returning HTTP 500

**Verified live** with a logged-in session probe (`_qa_probe.php`):

```
/fish-farm/reports/sales     -> 500
/fish-farm/reports/income    -> 500
(all other report routes   -> 200)
```

**Actual exception** (from `storage/logs/laravel.log` + the debug response):

```
ParseError: syntax error, unexpected token "endif", expecting end of file
  at storage/framework/views/....php:377   (source: resources/views/reports/sales.blade.php:72)
  at storage/framework/views/....php:377   (source: resources/views/reports/income.blade.php:67)
```

**Root cause.** `reports/sales.blade.php` and `reports/income.blade.php` pass their
extra filter markup as a **string literal** into `@include('reports._filters', ['extra' => '<x-form.field …>'])`.
Blade compiles `<x-…>` component tags *everywhere in the template text*, including inside
that string literal, emitting an unbalanced `@component … renderComponent/endif` block into
the compiled parent view — which then fails to parse. The two views that include a
`:selected`/`:options` binding in the `extra` string are the two that break.

**Fix (root cause, no faking):** replace the string-literal `extra` mechanism with a proper
Blade **named slot** (`filters`) rendered by the partial; the two broken views pass real
component markup as slot content (compiled correctly), and the partial falls back to the
legacy `extra` string for any untouched caller.

### BUG #2 — missing export route

Audit of `routes/web.php`: every report has `reports.export` and a per-module export route.
The earlier "missing export" was `finance.profit-loss.export` — **already routed and fixed**
in the prior session (registered inside the `permission:finance.view` group; the view's button
repointed from the wrong CSV). Verified 200:

```
/fish-farm/reports/sales/export        -> 200
/fish-farm/finance/profit-loss/export  -> 200
```

CSV stays a real streamed browser download (`CsvExporter::download`), filter-aware.

### Pricing

Per brief §22/§10 no pricing defect is assumed. Prior audit found the money engine correct
(`decimal(15,2)`, server-authoritative `round(x,2)`, single `LedgerRules::netProfit()`).
Re-verified this session: no reproducible discrepancy found. **Existing engine preserved.**

---

## 5. Migration dependencies & risks

| Risk | Mitigation |
| --- | --- |
| 148 Blade views — cannot convert all at once | Inertia root + shared props first; migrate pages module-by-module, keep Blade fallback |
| `@vite` root layout must serve both worlds | New `resources/views/app.blade.php` (Inertia root); existing views keep `components.layout.app` |
| Existing async JS (`X-Requested-With`/JSON) vs Inertia | Inertia requests are XHR with `X-Inertia` header; `AsyncResponse` unaffected for non-Inertia calls |
| Session/auth must survive | Inertia uses the same `web`/`auth` middleware + CSRF; no auth change |
| Tailwind 4 `@source` must scan React `.jsx` | Add `@source '../**/*.jsx'` to `resources/css/app.css` |
| npm not on PATH in shell | Invoke `C:\Program Files\nodejs\npm.cmd` explicitly |

---

## 6. Decisions taken

1. **Install Inertia (server) + `@inertiajs/react` + `react` + `react-dom` + `@vitejs/plugin-react`.**
   Pin versions compatible with Laravel 12 / PHP 8.2 / Vite 7.
2. New root template `resources/views/app.blade.php`; new entry `resources/js/app.jsx`.
   The old `resources/js/app.js` (vanilla) stays for the not-yet-migrated Blade pages.
3. Shared props: only `auth.user` (id/name/email), `flash`, `ziggy`-style route names **not**
   added — route URLs are passed per page (keeps global props small, brief §31).
4. Business logic untouched. Controllers gain an Inertia branch (`Inertia::render`) alongside
   the existing Blade branch during transition.
5. No DB migration. No destructive command. No fake data. No test suite. No git push.

See `docs/ERP-INERTIA-MIGRATION.md` for the target architecture and per-phase record.