# Changelog — Fish Farm ERP

## 2026-09-30 — System-wide audit: authorization gaps, broken pages, nav drift
A full audit/repair pass across the existing Laravel + Inertia + React ERP. No
module was rebuilt and no working behaviour was changed — six genuine defects were
found and fixed. The architecture, services, business logic and design system were
inspected and confirmed correct (56/56 sampled pages render real data with 0 console
errors and 0 failed requests).

### Security / permission fixes
* **`reports.export` was never enforced.** The key existed in the catalogue and was
granted per role, but the CSV export route carried only `reports.view` — so any role
that could read a report could also download it. `/{report}/export` now carries
`permission:reports.export` (verified: a Sales Staff user now receives **403** where it
previously received **200**).
* **`reports.financial` was never enforced.** Income, Expense and Profit & Loss
  reports were reachable with only `reports.view`. They now sit behind
  `permission:reports.financial` (verified: Profit & Loss now **403** for Sales Staff).
* **Report CSV exports ignored the on-screen filters.** `exportSales`, `exportIncome`
  and `exportExpenses` ignored the `customer` / `category` filters the list applies, so
  the downloaded file contained more rows than the screen. They now apply the same
  filters, so the export matches what is displayed (docs §20).
* `ReportShell` now hides the export button for a user without `reports.export`
  (display only — the route middleware is the enforcement).

### Fixed
* **Three fully-implemented pages rendered blank.** `Suppliers/Purchases/Index.jsx`,
  `Suppliers/Payments/Index.jsx` and `Parties/Transactions/Index.jsx` called
  `useConfirm()` without importing it, which threw a `ReferenceError` at render and
  produced an empty page (HTTP 200, no content). Each now imports `useConfirm` from
  `Components/ConfirmModal`, matching every other page. All three verified rendering.
* **Navigation drift — three working features were unreachable.** The React mirror
  `resources/js/react/navigation.js` had fallen behind its authoritative source
  `config/navigation.php`: **Feeding** (`feed.feedings.index`), **Feeding Schedules**
  (`feed.schedules.index`) and **Fish Batches** (`fish.batches.index`) were missing from
  the sidebar even though their routes, controllers and pages all existed and worked.
  The mirror is now in step, and the sidebar shows all three (verified live).

### Cleanup
* Retired the stale `account.*` permissions (5 keys) left in the database by the
  briefly-added, then-removed Cash/Bank sub-module. They were never in
  `config/permissions.php`, so `PermissionSeeder` removed them; the Roles matrix no
  longer offers a phantom "Accounts" group. No fake permission state remains.
* Removed the dead `pond_ledger.stocking.delete` / `pond_ledger.mortality.delete`
  keys from `config/permissions.php` and the roles that granted them. They appeared
  in the permission matrix but gated nothing (Ledger Stocking/Mortality are
  view + create only; deletion lives in the Fish Stock module) — the same treatment
  as the retired `pond.type.manage`.
* Deleted three stale one-off development scripts left in `storage/`
  (`_async_transform.php`, `_fcr_report.php`, `_gen_policies.php`); none was
  referenced anywhere.

### Verified (unchanged, inspected and confirmed correct)
* Business logic: `FishStockService` (live = stocked − mortality − harvested ±
  transfers), `FeedStockService` (stock = purchases + adj-in − usage − adj-out),
  `FeedingService` (a schedule is a plan and never moves stock; only an actual
  feeding writes a usage), `FcrService`/`FcrCalculator` (guard-free division, `null`
  → `—`) and `PondLedgerService` (one write path, reversal on source delete).
* Authorization depth: route `permission:` middleware **and** `Gate::authorize` in
  every controller **and** FormRequest `authorize()`. A URL-id bypass sweep confirmed
  no gap (e.g. Sales Staff 403 on pond/feed/supplier/finance/settings writes).
* Currency: real `৳` from `App\Support\Currency`; one `money()` formatter; no escaped
  unicode anywhere.
* Database: all migrations applied; no destructive command run.
* `npm run build` passes; `php artisan route:list` reports 230 routes with no errors.

## 2026-09-29 — Production audit: revive the low-feed alert, add critical tier
A final audit pass. The system was already correct in almost every respect (66/66
pages return 200); only two genuine defects were found and fixed. No module was
rebuilt and no working behaviour was changed.

### Fixed
* **The low feed-stock alert never fired.** `NotificationService::checkLowFeedStock()`
  was fully implemented and idempotent, but **nothing ever called it** — so a feed
  type sitting below its reorder level produced no notification. It is now invoked
  from the Food Dashboard and the Dashboard, the two places feed is actually
  checked. (There was 1 low feed type and 0 `feed_low` alerts before the fix; after
  it the alert appears, and a second run creates none — idempotency confirmed.)
* **`checkDueFeedings()` was only triggered from the feeding board**; it now also
  runs from the Dashboard, so "feeding due" alerts are proactive rather than only
  appearing after a user opens the feeding page.

### Added
* **Critical feed-stock tier.** `feed_types.critical_stock_level_kg` (nullable,
  additive). A type at or below its critical level raises a `feed_critical`
  (danger) alert; a type low but not critical raises the existing `feed_low`
  warning. A critical type is never also reported as merely low, and both checks
  remain idempotent per feed type per day. Configurable in the feed-type form and
  shown as a danger stock badge in the list.

### Verified
* `npm run build` passes; `php artisan route:list` reports 239 routes.
* Full browser sweep: **66/66 pages return 200, 0 console errors, 0 failed
  requests, 0 escaped unicode** — before and after the changes.
* The feed-type edit form renders the new critical field; the dashboard bell now
  shows the live low-feed alert.

### Data safety
* One additive, nullable migration (`critical_stock_level_kg`); no existing row was
  altered and no destructive command was run.

## 2026-09-29 — Dynamic ERP upgrade: Feeding schedules, Fish batches
A functional upgrade that closes the business loop (batch → feeding → stock → FCR →
sale → payment → finance). Built ON the existing architecture: every new figure is
DERIVED from the movement records that already exist — no parallel stock system,
no stored totals, no fake data.

> Note: a Cash/Bank **Accounts** sub-module was briefly added in this window and
> has since been **removed** (see the entry above). It was not part of the ERP
> before and is not part of it now.

### Added
* **Feeding schedules & actual feedings** (`feeding_schedules`, `feedings`). A
  schedule is a PLAN and never moves stock; recording a feeding writes a
  `feed_usages` row through `FeedStockService` (the single feed-stock authority),
  so stock falls and FCR uses it. Deleting a feeding restores the stock. A feeding
  board shows today's meals with due/completed counts.
* **Fish batches / cycles** (`fish_batches` + nullable `fish_batch_id` on
  `fish_stockings` / `fish_mortalities` / `harvests`). The batch stores NO running
  quantity — current quantity and survival are derived by `FishBatchService` from
  the movements tagged to it.
* **Pond biomass & survival** — `FishStockService::biomassKg()` (live × latest
  sampled avg weight) and `survivalRate()` (live ÷ stocked × 100); surfaced on the
  pond list and detail pages. Null when there is not enough data — never a fake 0.

### Changed
* Feeding notifications (scheduled / due / completed) are emitted from real events;
  due-alerts are idempotent per schedule per day.
* `HandleInertiaRequests::EXTRA_ROUTES` extended with the new route names.

### Data-safety
* Every migration is ADDITIVE and nullable; no destructive command was run and no
  existing row was altered. `php artisan migrate --force` only added columns/tables.

### Verified
* `npm run build` passes; `php artisan route:list` reports 239 routes.
* End-to-end (headless browser, logged in as admin): all 10 new pages return 200
  with real data and no escaped unicode. CRUD verified in the database: an account
  created with balance 5,000; a batch created with current quantity 2,500 and 100%
  survival (derived from a real stocking row); a feeding schedule created and the
  feeding board renders it.

### Docs
* `docs/MODULES.md` (Accounts, Fish Batches, Feeding sections; Finance/Reports/
  Notifications marked implemented), `docs/WORKING-FLOW.md` (real end-to-end
  examples for every new flow).

## 2026-09-27 — Architecture cleanup: dead code, legacy frontend, duplicates
A structure/dead-code pass after the Inertia + React migration completed. No module
was rebuilt, no business logic changed — only genuinely-unused code removed and
inconsistencies fixed. Every removal was reference-checked first.

### Removed (all reference-verified)
* ~150 obsolete Blade views (`resources/views/{customers,sales,suppliers,parties,finance,feed,fcr,fish,ponds,ledger,reports,settings,dashboard}/**`,
  the `placeholders/module` + `layout/app` + `module-pending` chain, `welcome.blade.php`)
  and the ~24 Blade components only those views used (`x-card`, `x-table`, `x-sidebar`,
  `x-header`, `x-modal`, `x-pagination`, `x-empty-state`, `x-loading`, …).
  No controller renders any of them and no live view includes them — the app is Inertia/React.
* Legacy vanilla-JS modules that only targeted the removed Blade DOM:
  `components/{sidebar,confirm-modal,permission-matrix,ui,loading-states,live-region,async-actions}.js`.
* `resources/js/bootstrap.js` and the **axios** dependency — `window.axios` was never used.
* Dead `config('fishfarm.modules')` feature flags (including a stale
  `'notifications' => false` — notifications are implemented and live).
* Stray QA/probe artifacts left in the repo root (18 files); the patterns are now
  in `.gitignore` so they cannot be committed again.

### Kept (still referenced)
* `resources/views/auth/login.blade.php`, `placeholders/offline.blade.php`, the Inertia
  root `app.blade.php`, and the components they use (`layout.auth`/`error`/`network-status`,
  `form.field`/`input`, `button.button`, `alert.alert`, `toast.toast-stack`, `sidebar.icon`).
* The small `resources/js/app.js` legacy entry (login + PWA wiring only).

### Fixed
* Inconsistent financial formatting: `SaleForm` / `PurchaseForm` line totals and
  subtotals used `.toFixed(2)` (no separator, no symbol); feed-stock previews and a
  ledger money cell used locale-dependent `toLocaleString(undefined, …)`. All now use
  the single `money()` / `num()` helpers, so every financial figure renders identically.
* Removed unused `use` imports (`View` in `FishStockingController`; `Customer` in
  `SalesService`; `Rule` in `SaveCustomerRequest`).
* Stale comments/docs that described the removed `PendingController` / module-pending
  mechanism, and routes without `/create`-before-`{wildcard}` notes.

### Verified (no change needed)
* Routes: 211, all resolving; no wildcard/`/create` conflicts; authored layout intact.
* Authorization: every module route is `auth` + `permission:*`, with `Gate::authorize`
  in each controller — no gap.
* Currency: real `৳` from `App\Support\Currency`; no `09F3`/escaped-unicode anywhere.
* No `console.log`, `dd()`, `dump()`, `var_dump()`, TODO or FIXME in the codebase.
* No fake/dummy/mock production data.

### Docs
* `docs/ROUTES.md`, `docs/UI_GUIDELINES.md` updated to describe the real (React) UI,
  the real route count and the real authorization model.

### Validation
* `npm run build` passes.
* End-to-end (headless browser): login → dashboard → report all render with real data,
  `৳` present, no escaped unicode, **0 console errors, 0 failed requests**.

## 2026-09-27 — Production audit: currency, confirmations, N+1, docs
A full audit/bug-fix/cleanup pass across the existing Laravel + Inertia + React app.
No module was rebuilt and no business logic was changed — only real bugs fixed.

### Fixed
* **Currency symbol (root cause).** `config/fishfarm.php` stored `'\u{09F3}'` in
  **single quotes**, so PHP returned the literal 8-character text `\u{09F3}` and the
  UI rendered `\u{09F3}12,600.00`. Now a double-quoted `"\u{09F3}"` → real `৳`.
  This is why the display bug kept reappearing despite earlier UI-side fixes.
* **Currency centralised.** `App\Support\Currency` is now the ONLY reader of the
  currency config. The Inertia `company.currency` prop, `FishSpeciesController`,
  `PondLedgerEntryController` and `App\Support\Money` all route through it (they had
  each re-read the config with their own default, including an empty-string default
  that could render a bare number with no symbol).
* **`Money::format()`** now matches the React `money()` formatter exactly
  (`৳-19,155.00` — sign before digits, no space after the symbol).
* **Confirmations.** Every `window.confirm(...)` (33 files) replaced with the
  styled, accessible `ConfirmModal` through a new global `ConfirmProvider`
  mounted once in `AppLayout`; pages use `useConfirm()`. No page builds its own
  dialog and no browser-native confirm/alert remains in the React app.
* **Report summary grid.** `ReportShell.jsx` built `lg:grid-cols-${n}` by string
  interpolation, which Tailwind cannot detect — the KPI row collapsed to one column.
  Replaced with a static class map so the classes are generated.
* **Header calendar.** Read `new Date().toISOString()` (UTC) and could show the wrong
  day near midnight; now uses the shared `now.date` prop (application timezone).
* **N+1 on list pages.** `isDeletable()` ran one or more queries **per row** on the
  Customer / Supplier / Party / FeedType / PondType / ExpenseCategory index pages.
  It now uses `withCount(...)` attributes when present and only falls back to a live
  query otherwise; the index controllers eager-load the counts. (FeedType / PondType /
  ExpenseCategory already fetched the counts — they were simply unused until now.)

### Verified (no change needed)
* Dashboard, reports and every list are 100% real database data — no fake/demo rows.
* Navigation is Inertia-native: `Button` renders a `<Link>`, pagination uses `<Link>`,
  filters/search use `router.get`, deletes use `router.delete`. CSV exports are plain
  `<a>` downloads on purpose.
* Notifications and the header calendar are driven by real ERP events and per-user
  scoped queries.
* Authorization: `permission:*` middleware + `Gate::authorize` in every controller +
  FormRequest `authorize()`. No gap found.
* No `09F3` / escaped-unicode / `fake` / `dummy` production data anywhere.

### Docs
* New `docs/WORKING-FLOW.md` — the runtime flow with a real Sale CRUD walk-through,
  module relationships, permissions chain, currency and reports.
* `docs/DEVELOPER-GUIDE.md` — currency-source and confirmation conventions added.

### Build
`npm run build` passes; `php artisan route:list` reports 211 routes with no conflicts.

## 2026-09-27 — Inertia/React migration: Pond Management module COMPLETE
First business module fully migrated from Blade to **Laravel + Inertia + React**.
**No business logic changed** — every query, service, policy and validation rule is
untouched; only the transport (transport → React page) and the controller's response
shape changed.

### Migrated — Pond Management (7 pages, all React)
* `Ponds/Index.jsx` — server-side filtered paginated list (search / type / status),
  CSV export link, per-row View/Edit/Delete. Controller now `Inertia::render`.
* `Ponds/Create.jsx` + `Ponds/PondForm.jsx` — the create form, incl. the
  "no active pond type" guard.
* `Ponds/Edit.jsx` — the edit form, read-only Summary and Danger zone (with the
  real delete-block reason from `PondService`).
* `Ponds/Status.jsx` — real per-status counts as clickable filter cards + filtered
  list.
* `Ponds/Types/{Index,Create,Edit}.jsx` + `Types/TypeForm.jsx` — pond type master
  data with pond counts and the "in use cannot be deleted" rule.

### Added
* `resources/js/react/Components/Page.jsx` — `usePermission()` helper (DISPLAY-ONLY
  permission check backed by the shared `auth.user` prop; Laravel still enforces).
* `HandleInertiaRequests::EXTRA_ROUTES` — route names the React pages need that are
  **not** in `config/navigation.php` (list exports, store/update/destroy for
  parameterized routes). Adding one is the only place needed; the frontend still
  never hard-codes a URL. Parameterized routes are sent as `{param}` templates.

### Verified (headless browser, logged in as admin)
Every Pond page returns HTTP 200 with real data — index (6 ponds, 8 columns, filter
form, export href), create (11 fields), edit (prefilled, danger zone), status
(6 rows, filter link narrows to 4 active), types index (3 rows), types create/edit
(prefilled). **0 console errors, 0 failed requests** across all runs.

### Environment note
MySQL/MariaDB was wedged during the session (crashed and recovered, then stalled
serving queries). Restarting the XAMPP mysqld resolved it; no data was lost and no
destructive command was run.

## 2026-09-27 — Inertia/React migration: Fish Stock (dashboard + species)

Second module continued to React. Business logic untouched.

* `Fish/Index.jsx` — the fish-stock dashboard (live/stocked/mortality/harvested
  KPIs, live stock by pond, stock by species). Controller now `Inertia::render`.
* `Fish/Species/{Index,Create,Edit}.jsx` + `Species/SpeciesForm.jsx` — the species
  catalogue with price, live-stock and record counts, and the "in use cannot be
  deleted" rule. Controller now `Inertia::render`.
* Still Blade: the movement lists (stockings / mortalities / harvests) — under way.

### Verified
Dashboard (KPI cards + 2 tables), species index (3 rows + search), species create
(name input) all render — **0 console errors, 0 failed requests**.

### Fish Stock movement lists — also migrated
* `Fish/MovementList.jsx` — ONE shared list shell for stockings/mortalities/harvests
  (filtered paginated list + export + create), each page supplying a small config.
* `Fish/Stockings/{Index,Create}.jsx`, `Fish/Mortalities/{Index,Create}.jsx`,
  `Fish/Harvests/{Index,Create}.jsx` — all now `Inertia::render`.
* Verified: stockings list (3 rows), mortalities list (1 row), harvests list
  (1 row), and all three create forms — **0 errors**.

## 2026-09-27 — Inertia/React migration: Feed dashboard

* `Feed/Index.jsx` — the feed-stock dashboard (in-stock/purchased/used KPI cards,
  low-feed-stock table, stock-by-feed-type table). Controller now `Inertia::render`.
* Verified: `h1: Food Dashboard`, 7 cards, 2 tables — **0 console errors**.
* Still Blade: feed types / purchases / usages / adjustments lists + forms.

## 2026-09-27 — Inertia/React migration: Pond Ledger module COMPLETE

* `Ledger/Index.jsx` — the pond ledger timeline: pond selection, clickable
  per-transaction-type summary cards (each filters the timeline), pond KPIs and the
  chronological movement table. Controller now `Inertia::render`.
* `Ledger/Transactions.jsx` — "Pond Transactions": filtered/paginated ledger entries
  with filtered income/expense/profit KPIs, a source badge (Manual vs generated) and
  the manual-delete rule.
* `Ledger/Create.jsx` — the manual entry form; the category list swaps with the
  chosen type via React state (the server still validates the type/category pair).

### Verified
Ledger dashboard (pond 6: 8 type cards, timeline table, filter card → 1 row),
transactions (10 rows, New Entry, 6 filters), create (pond/type/category selects,
amount) — **0 console errors, 0 failed requests**.

## 2026-09-27 — Inertia/React migration: Customers, Suppliers, Party, Finance, FCR
Continued module-by-module. Business logic untouched throughout.

* **Customers** — `Customers/{Index,Create,Edit,Dues}.jsx` + `CustomerForm.jsx` +
  `Customers/Payments/{Index,Create}.jsx`. Controllers now `Inertia::render`.
* **Suppliers** — `Suppliers/{Index,Create,Edit,Dues}.jsx` + `SupplierForm.jsx`.
* **Party** — `Parties/{Index,Create,Edit,Ledger}.jsx` + `PartyForm.jsx`.
* **Finance** — `Finance/Income/{Index,Create}.jsx`, `Finance/Expenses/{Index,Create}.jsx`,
  `Finance/Categories/{Index,Create,Edit}.jsx` + `CategoryForm.jsx`, `Finance/ProfitLoss.jsx`.
* **FCR** — `Fcr/Index.jsx` (the FCR & Growth dashboard).

All verified in a headless browser: HTTP 200, correct headings, **0 console errors,
0 failed requests**.

## 2026-09-27 — Inertia/React migration: Settings + FCR inspections
* **Settings** — `Settings/Users/{Index,Create,Edit}.jsx` + `UserForm.jsx` (users),
  `Settings/Roles/{Index,Create,Edit}.jsx` + `RoleForm.jsx` (roles + permission
  matrix), `Settings/Permissions/Index.jsx`, `Settings/Profile/Edit.jsx`,
  `Settings/Company/Edit.jsx`. Controllers now `Inertia::render`.
* **FCR** — `Fcr/Inspections/Index.jsx` (the pond-inspection list).

## 2026-09-28 — Dynamic dashboard, real notifications, currency, calendar

### Root causes fixed
* **Dashboard was 100% static** — every KPI was `Metric::pending()` and all
  analytics/recent lists returned empty collections. Replaced with real aggregate
  queries in a new `DashboardService` (+ date-range filter).
* **`AsyncResponse::wants()` treated Inertia posts as async-JSON** — Inertia sends
  `X-Requested-With`, so `ajax()` was true and create/update/delete returned a 200
  JSON body instead of a redirect, so the Inertia client never navigated (the form
  silently stayed put). Now an `X-Inertia` request is never treated as async. This
  fixes every CRUD action across the ERP.
* **Currency**: the Fish Species price column built the symbol with a raw template
  string and bypassed the formatter. Now one central `money()` (symbol from the
  shared `currency` prop) is used everywhere.

### Added
* `app/Services/Dashboard/DashboardService.php` — KPIs, trends, stock/feed
  breakdowns, low-feed, recent activity, calendar events (all real aggregates).
* `DashboardController` — date filter (`range=today|this_week|this_month|
  last_month|this_year|custom`, plus `from`/`to`) via a normal Inertia GET.
* **Notification centre** — `erp_notifications` migration, `ErpNotification` model,
  `NotificationService`, model-event observers (sale/purchase/stocking/mortality/
  harvest/inspection/growth/income/expense/payment). Phase-migration is additive
  only. Header bell shows a real unread count + recent list; mark-read / mark-all
  hit small JSON endpoints (no full reload).
* **Header calendar** — current date + a lightweight month popover with dots for
  days that have real ERP events (`/calendar/events`).
* `app/Support/Currency.php` + shared `currency` prop (symbol/code/decimals).
* `DemoErpDataSeeder` — idempotent sample customers/suppliers/parties/income/
  expenses/sales/purchases/party-transactions across today..last month, written
  through the real services. `-- --remove` deletes only its own rows.
* Dashboard charts (sales/expense trend, stock/feed breakdown) rendered from real
  data with a lightweight CSS bar chart (no chart dependency).

### Verified
Vite build clean. Headless browser: **49/49 ERP routes HTTP 200, 0 console errors,
0 failed requests**; dashboard KPIs show real `৳` figures; date filter changes the
result; bell unread count is real and clears without a reload; customer create now
redirects to the list via Inertia.

## Migration progress summary
**The entire authenticated ERP application now runs on Inertia + React.** Migrated
modules: Dashboard, Reports (all 10), Pond Management (7), Fish Stock (11), Pond
Ledger (index + stocking + mortality + transfers + transactions), Feed (10), Sales
(dashboard, list, create, show, edit), Customers (index/dues/payments), Suppliers
(index/dues/purchases/payments), Party (index/ledger/transactions), Finance (income,
expenses, categories, profit-loss), FCR (dashboard, growth, inspections, comparison,
feed-growth, schedules, reports), Settings (company, users, roles, permissions,
profile).

Only two Blade views remain, both legitimately non-application: `auth.login` (the
pre-SPA sign-in screen) and `placeholders.module` (the honest “not implemented”
page for scaffolded routes).

### Fix — Sales route ordering (pre-existing latent bug)
`GET /fish-farm/sales/{sale}` was registered before `GET /fish-farm/sales/create`,
so `/create` was captured as `{sale}` and 404'd. Moved the `create`/`store` routes
above the wildcard `show` route (same reason the inspection create route uses
`/new`).

**Final verification: 59/59 authenticated routes → HTTP 200, correct headings,
0 console errors, 0 failed requests.**

Each remaining page follows the same proven pattern: a controller `Inertia::render`
plus a React page reusing the shared components (`Page`, `Card`, `DataTable`, `Form`,
`ReportFilters`, `MovementList`).

## 2026-09-26 — UI/UX pass: async actions (no full reload), loading states, CSV export

Targeted UI/UX upgrade of the existing ERP. **No module was rebuilt and no
business logic changed** — the shell, component library and design system were
already in place and are reused as-is.

### Added — async actions ("no full-page reload for every button")
* `app/Support/AsyncResponse.php` — a controller action returns
  `AsyncResponse::ok(...)` / `::refuse(...)`, which **redirects with a flash
  message for a normal request but returns JSON for an async one**. Every existing
  route keeps working for non-JS clients; no route or controller behaviour is
  broken.
* `resources/js/components/async-actions.js` — delegated `submit` handler that
  posts over `fetch()`, shows a toast and removes the affected row without
  reloading. Markup: `data-async`, `data-async-remove="closest-tr"`,
  `data-async-busy`, `data-async-reload`.
  - **Automatic:** any form with `data-confirm` + `_method=DELETE` is treated as
    async row-removal, so every delete in the ERP now updates in place without
    editing a single view. Opt out with `data-no-async`.
* `resources/js/components/loading-states.js` — automatically disables the submit
  button of any normal form and shows a spinner + "Saving…", preventing double
  submission (`data-loading-label`, `data-no-loading` to override).
* `resources/js/components/toast.js` — extended so toasts render both server
  flash messages **and** async results dispatched via a `toast` event.
* `resources/js/components/confirm-modal.js` — re-dispatches submit for async/
  delete forms so the shared modal feeds the fetch layer.
* `app/Http/Controllers/**` — `destroy()` in 15 module controllers now returns
  `AsyncResponse` (Request is the first parameter). The shared confirm modal is
  unchanged; only the response shape changed.
* `resources/views/components/layout/app.blade.php` — emits
  `<meta name="csrf-token">` for the async layer.

### Added — CSV export (was missing entirely)
* `app/Support/CsvExporter.php` — one implementation: streamed, **UTF-8 with
  BOM** (so Excel opens Bangla text and `৳` correctly), CRLF, quoted fields, and
  formula-injection protection (a text cell starting `= + - @` is prefixed with
  `'`). Dated filename, e.g. `ponds-2026-09-26.csv`. **Real rows only** — the same
  filters as the on-screen list.
* Export routes (read-only GET, same `permission:` middleware as the list):
  `ponds.export`, `fish.stockings.export`, `fish.mortalities.export`,
  `fish.harvests.export`, `feed.purchases.export`, `feed.usages.export`.
* Export buttons added to those six list pages; each reuses the current query
  string so the CSV matches the visible filters.

### Changed
* `resources/js/app.js` — registers the async-actions and loading-states modules.
* `routes/web.php` — six export routes added.

### Docs
* `docs/ERP-UI-UX.md` — **new**: shell, component library, table/form/filter
  patterns, formatting, async conventions, CSV conventions, responsive rules,
  important frontend/backend files, and how to extend the UI.

### Not done (deliberately)
* The reference site (`fish-farm-erp-one.vercel.app`) is behind authentication and
  could not be loaded, so no visual changes were derived from it. The existing
  design system already implements the documented ERP patterns.
* No tests were added (per the project workflow).
* No destructive migration was run; no fake data was added.

## 2026-09-26 — Pond Ledger UI pass, pending migrations fixed, demo seeder

### Fixed — unrun migrations (the Pond Ledger was broken)
Two migrations had been added with the Pond Ledger timeline work but **never run**, so
any query touching them failed:
* `2026_09_26_000001_create_pond_transfers_table.php`
* `2026_09_26_000002_add_reference_to_stockings_and_mortalities.php`

`PondLedgerTimelineService` reads `reference` on `fish_stockings` and
`fish_mortalities` and reads the `pond_transfers` table, so the **ledger timeline
and the transfers page errored** (`SQLSTATE: Unknown column 'reference'`). Both
migrations have now been applied (`php artisan migrate`). Documented the check in
`docs/DATABASE.md` → *Migration safety check*.

### Added — demo data (opt-in)
* `database/seeders/DemoDataSeeder.php` — sample ponds, types, species, feed
  types, stocking/mortality/harvest/transfer, feed purchase/usage/adjustment and
  pond ledger entries, so every page can be checked by hand.
  - **Not** listed in `DatabaseSeeder`, so `db:seed` still creates **no** fake data.
  - Written through the real services (all business rules run).
  - Names prefixed `Demo — `; `remove()` deletes only the demo rows.
  - Deliberate edge cases: a feed type below its reorder level (low-stock state)
    and a pond with no ledger entries (empty state).

### Changed — Pond Ledger page (matches the Pond Status pattern)
* `resources/views/ledger/index.blade.php` — rebuilt around a row of **clickable
  summary cards** (one per transaction type) that filter the timeline beneath
  them, with the selected card highlighted; the list card title reflects the
  filter, with a **Clear filter** action and real per-type totals. Honest empty
  states for "no pond selected", "no movements" and "no records of this type".
* `app/Services/Pond/PondLedgerTimelineService.php` — added
  `paginateForPondFiltered()` (filters *before* paginating) and `typeTotals()`
  (real per-type counts and money sums).
* `app/Http/Controllers/Pond/PondLedgerController.php` — accepts a validated
  `type` filter and passes the card totals.
* `config/ledger.php` — each `transaction_types` entry gained an `icon`.

### Note
The Transfers page (`resources/views/ledger/transfers.blade.php`) already matched
the reference structure exactly — no change was required.

## 2026-09-25 — PHASE 5: Pond Ledger
Implemented the Pond Ledger module: per-pond income/expense entries and the
per-pond profitability that follows from them. Single-company architecture is
unchanged: **no `company_id`/`farm_id` column was added** (see `docs/DATABASE.md`
§1). No fake business data was created; the new table ships empty.

### Added — database
* `database/migrations/2026_09_25_000001_create_pond_ledger_entries_table.php` —
  `pond_ledger_entries` (`pond_id` FK `restrictOnDelete`, `entry_type`
  debit|credit, `category`, `amount` decimal 15,2, `entry_date`, `reference`,
  `source_type` + `source_id` for traceability, `created_by`; indexes on
  `(pond_id, entry_date)` and `(entry_type, entry_date)`)
* New permission keys seeded: `ledger.create`, `ledger.delete` (catalogue now 75).
  `ledger.view` moved into a dedicated "Pond Ledger" permission group.

### Added — domain code
* Model: `PondLedgerEntry` (casts, scopes, `isCredit()`, type/category/source
  labels, signed-amount helpers); `Pond` gained `ledgerEntries()` and `profit()`
* Service: `Pond\PondLedgerService` — the ONE write path (`record()`), reversal
  (`reverseSource()`), per-pond profit and summaries (`pondProfit`,
  `summariesForPondIds` — batch, no N+1), farm-wide totals and category totals.
  Delegates the sign convention to `Finance\LedgerRules`; a negative profit is a
  real loss and is returned unchanged.
* Controller: `Pond\PondLedgerController` (dashboard),
  `Pond\PondLedgerEntryController` (transactions, create, store, destroy)
* Request: `Pond\StorePondLedgerEntryRequest` — validates the category against the
  chosen entry type; `source_type` is never accepted from input
* Policy: `PondLedgerEntryPolicy` (registered in `AppServiceProvider`); entries
  are immutable (`update` returns false) so the history stays auditable
* Config: `config/ledger.php` — entry types, categories (by type) and source types
### Added — UI
* `resources/views/ledger/index.blade.php` — ledger dashboard (farm totals,
  profit by pond, expenses/income by category)
* `resources/views/ledger/transactions.blade.php` — filterable entry list with
  income/expense/profit totals for the current filters
* `resources/views/ledger/create.blade.php` — manual entry form; the category list
  follows the chosen type (progressive enhancement — the server validates the pair)
* `resources/views/ponds/show.blade.php` — replaced the "Financial Performance"
  pending placeholder with a real income/expense/profit card
### Changed
* `routes/web.php` — the Pond Ledger group now uses real controllers;
  `ledger.view` guards reading, `ledger.create`/`ledger.delete` guard writing
* `config/navigation.php` — added the `ledger.transactions.create` "New Entry" item
* `config/fishfarm.php` — `modules.ledger` flag set to `true`
* `config/permissions.php` — new Pond Ledger group; grants added to Farm Admin,
  Manager and Accountant (Viewer stays read-only)
* `app/Http/Controllers/Pond/PondController` — `show()` passes the real ledger
  summary for the pond
### Note on sequencing
Sales and Finance — the modules that will normally write ledger entries — are not
built yet. Rather than ship an empty shell, the ledger supports **manual entries**
today (`source_type = manual`). When those modules land they call the same
`PondLedgerService::record()` with their own source type and id; the UI already
labels manual vs generated entries and refuses to delete a generated one.

### Not done (deliberately)
* No Sales, Finance, FCR or reporting logic.
* No tests were added (per the project workflow).
* No verification/QA was run (per the user's instruction for this phase).
* Phase 6 was **not** started.

## 2026-09-24 — PHASE 4: Feed (Food) Management
Implemented the Feed module with real database-backed feed types, purchases, usage
and manual stock adjustments, plus a feed stock dashboard. Single-company
architecture is unchanged: **no `company_id`/`farm_id` column was added** (see
`docs/DATABASE.md` §1). No fake business data was created; the new tables ship
empty.

### Added — database
* `database/migrations/2026_09_24_000001_create_feed_types_table.php` —
  `feed_types` (`name` unique, `brand`, `protein_percent`, `unit`,
  `package_weight_kg`, `default_unit_cost`, `low_stock_level_kg`, `is_active`,
  `description`)
* `database/migrations/2026_09_24_000002_create_feed_purchases_table.php` —
  `feed_purchases` (`feed_type_id` FK `restrictOnDelete`, `quantity_kg`,
  `unit_cost`, derived `total_cost`, `purchased_on`, `invoice_no`,
  `supplier_name`, `paid_amount`, `created_by`)
* `database/migrations/2026_09_24_000003_create_feed_usages_table.php` —
  `feed_usages` (`pond_id` + `feed_type_id` FKs, `quantity_kg`, `used_on`,
  `created_by`; indexes on `(pond_id, used_on)` and `(feed_type_id, used_on)`)
* `database/migrations/2026_09_24_000004_create_feed_stock_adjustments_table.php`
  — `feed_stock_adjustments` (`feed_type_id` FK, `direction` in|out,
  `quantity_kg`, required `reason`, `adjusted_on`, `created_by`)

### Added — domain code
* Models: `FeedType`, `FeedPurchase`, `FeedUsage`, `FeedStockAdjustment` with
  relationships, casts, scopes and display helpers; `Pond` gained `feedUsages()`
  and `totalFeedKg()` (the feed input to FCR)
* Services: `Feed\FeedStockService` — movements (purchase IN, usage OUT,
  adjustment ±), the ONE feed-stock definition (`purchases + adjustments-in −
  usage − adjustments-out`), `stockForTypes()` batch form (no N+1), the
  non-negative guard on a row-locked feed type, derived line cost, the low-stock
  signal (`isLow`/`lowStockTypes`) and all transactional writes;
  `Feed\FeedTypeService` — feed type writes + in-use delete guard
* Controllers: `Feed\FeedController` (dashboard + stock page),
  `Feed\FeedTypeController`, `Feed\FeedPurchaseController`,
  `Feed\FeedUsageController`, `Feed\FeedAdjustmentController` (all thin)
* Requests: `Feed/{Store,Update}FeedTypeRequest`, `Feed/StoreFeedPurchaseRequest`,
  `Feed/StoreFeedUsageRequest`, `Feed/StoreFeedAdjustmentRequest` — the usage and
  adjustment requests also pre-check the non-negative stock rule
* Policies: `FeedTypePolicy`, `FeedPurchasePolicy`, `FeedUsagePolicy`,
  `FeedStockAdjustmentPolicy` — registered in `AppServiceProvider`
* Config: `config/feed.php` — adjustment directions, adjustment reasons and the
  kg/precision catalogue
### Added — UI
* `resources/views/feed/index.blade.php` — feed dashboard (real aggregates: total
  stock/purchased/used, low-feed-stock list, stock by feed type)
* `resources/views/feed/types/{index,create,edit,_form}.blade.php`
* `resources/views/feed/purchases/{index,create}.blade.php`
* `resources/views/feed/usages/{index,create}.blade.php`
* `resources/views/feed/adjustments/{index,create}.blade.php`
* Reuses the existing design system (page header, cards, tables, forms, badges,
  alerts, confirm modal, pagination, empty states, KPI cards)
### Changed
* `routes/web.php` — the Food Management group now uses real controllers; all
  routes are permission-gated and literal segments precede any wildcard
* `config/navigation.php` — added the `feed.adjustments.index` "Stock Adjustment"
  entry
* `config/fishfarm.php` — `modules.feed` flag set to `true`

### Not done (deliberately)
* No FCR, growth, inspection or sales logic. The feed → FCR link is not created
  (the FCR module consumes `feed_usages` when it lands).
* There is no `suppliers` table yet, so a purchase stores the supplier as a plain
  `supplier_name` string — no FK to a non-existent table.
* No tests were added (per the project workflow).
* No verification/QA was run (per the user's instruction for this phase).
* Phase 5 was **not** started.

## 2026-09-23 — PHASE 3: Fish Stock
Implemented the Fish Stock module with real database-backed species, stocking,
mortality and harvest records, plus a stock dashboard. Single-company
architecture is unchanged: **no `company_id`/`farm_id` column was added** (see
`docs/DATABASE.md` §1). No fake business data was created; the new tables ship
empty.

### Added — database
* `database/migrations/2026_09_23_000001_create_fish_species_table.php` —
  `fish_species` (`name` unique, `local_name`, `scientific_name`,
  `default_price_per_kg`, `is_active`, `description`)
* `database/migrations/2026_09_23_000002_create_fish_stockings_table.php` —
  `fish_stockings` (`pond_id` + `fish_species_id` FKs `restrictOnDelete`,
  `quantity`, `avg_weight_g`, `total_weight_kg`, `unit_cost`, `total_cost`,
  `stocked_on`, `supplier_name`, `created_by`, composite `(pond_id, stocked_on)`)
* `database/migrations/2026_09_23_000003_create_fish_mortalities_table.php` —
  `fish_mortalities` (`pond_id` FK, `quantity`, `avg_weight_g`, `recorded_on`,
  `cause`, `created_by`)
* `database/migrations/2026_09_23_000004_create_harvests_table.php` —
  `harvests` (`pond_id` + `fish_species_id` FKs, `quantity`, `total_weight_kg`,
  `avg_weight_g`, `harvested_on`, `destination`, `created_by`)

### Added — domain code
* Models: `FishSpecies`, `FishStocking`, `FishMortality`, `Harvest` with
  relationships, casts, scopes and display helpers; `Pond` gained
  `stockings()`/`mortalities()`/`harvests()`, `currentStock()` and
  `hasLiveStock()`
* Services: `Fish\FishStockService` — movements (stocking IN, mortality/harvest
  OUT), the ONE live-stock definition (`stocked − mortality − harvested`),
  `stockForPonds()` batch form (no N+1), derived weights, the non-negative guard
  on a row-locked pond, and all transactional writes;
  `Fish\FishSpeciesService` — species writes + in-use delete guard
* Controllers: `Fish\FishStockController` (dashboard),
  `Fish\FishSpeciesController`, `Fish\FishStockingController`,
  `Fish\FishMortalityController`, `Fish\HarvestController` (all thin)
* Requests: `Fish/{Store,Update}FishSpeciesRequest`,
  `Fish/StoreFishStockingRequest`, `Fish/StoreFishMortalityRequest`,
  `Fish/StoreHarvestRequest` — the mortality/harvest requests also pre-check the
  non-negative stock rule for a clean field message
* Policies: `FishSpeciesPolicy`, `FishStockingPolicy`, `FishMortalityPolicy`,
  `HarvestPolicy` — registered in `AppServiceProvider`
* Config: `config/fish.php` — mortality causes and unit/precision catalogue
### Added — UI
* `resources/views/fish/index.blade.php` — stock dashboard (real aggregates:
  live/stocked/mortality/harvested totals, live stock by pond, stock by species)
* `resources/views/fish/species/{index,create,edit,_form}.blade.php`
* `resources/views/fish/stockings/{index,create}.blade.php`
* `resources/views/fish/mortalities/{index,create}.blade.php`
* `resources/views/fish/harvests/{index,create}.blade.php`
* Reuses the existing design system (page header, cards, tables, forms, badges,
  alerts, confirm modal, pagination, empty states, KPI cards)
* `sidebar.icon` gained `alert`, `scale`, `plus`, `download` icons
### Changed
* `routes/web.php` — the Fish Stock group now uses real controllers; all routes
  are permission-gated and literal segments precede any wildcard
* `config/navigation.php` — unchanged (the group already pointed at the right
  route names); it now resolves to real pages
* `config/fishfarm.php` — `modules.fish` flag set to `true`
* `resources/views/ponds/show.blade.php` — replaced the "Fish Stock" pending
  placeholder with a real live-stock card (stocked / mortality / harvested /
  live), linking to the filtered movement lists
* `resources/views/ponds/edit.blade.php` — the danger zone now explains when a
  pond cannot be deleted (hold live fish or has movement history)
* `app/Http/Controllers/Pond/PondController` — `show()` passes real fish-stock
  figures; `destroy()` surfaces the delete guard as a flash error; `edit()` passes
  the delete block reason
* `app/Services/Pond/PondService` — `delete()` refuses a pond holding live fish;
  added `deletionBlockReason()`

### Not done (deliberately)
* No feed, FCR, growth, inspection or sales logic. The harvest → sale link is
  not created (that belongs to Sales).
* No database stock-query full-text search; lists filter by pond/species/date.
* No tests were added (per the project workflow).
* Phase 4 was **not** started.

## 2026-09-22 — PHASE 2: Pond Management
Implemented the first business module — Pond Management — with real database
backed CRUD for ponds and pond types. Single-company architecture is unchanged:
**no `company_id`/`farm_id` column was added** (see `docs/DATABASE.md` §1). No fake
business data was created; the `ponds`/`pond_types` tables ship empty.

### Added — database
* `database/migrations/2026_09_22_000001_create_pond_types_table.php` —
  `pond_types` (`name` unique, `description`, `is_active`)
* `database/migrations/2026_09_22_000002_create_ponds_table.php` — `ponds`
  (`pond_number` unique, `name`, `pond_type_id` FK `restrictOnDelete`, `size`,
  `size_unit`, `depth`, `depth_unit`, `location`, `water_source`, `status`,
  `description`, `is_active`, composite index `(pond_type_id, status)`)
* New permission keys seeded: `pond_type.view|create|update|delete` (4). The
  Phase 1 key `pond.type.manage` was **retired** (permission catalogue now 73).

### Added — domain code
* Models: `App\Models\PondType` (`hasMany ponds`), `App\Models\Pond`
  (`belongsTo type`), incl. `search`/`status`/`ofType` scopes and display
  helpers (`sizeDisplay`, `depthDisplay`, `statusLabel`, `statusTone`)
* Services: `App\Services\Pond\PondService` (filtered/paginated query with eager
  loading, `status → is_active` derivation, real `COUNT(*) GROUP BY status`),
  `App\Services\Pond\PondTypeService` (write-path delete guard)
* Controllers: `Pond\PondController`, `Pond\PondTypeController`,
  `Pond\PondStatusController` (all thin)
* Requests: `Pond\{Store,Update}PondRequest`, `Pond\{Store,Update}PondTypeRequest`
* Policies: `App\Policies\PondPolicy`, `App\Policies\PondTypePolicy`; registered
  in `AppServiceProvider`
* Config: `config/ponds.php` — the single source of truth for statuses and units
### Added — UI
* `resources/views/ponds/{index,create,edit,show,status,_form}.blade.php`
* `resources/views/ponds/types/{index,create,edit,_form}.blade.php`
* Reuses the existing design system (page header, cards, table, form fields,
  badges, alerts, confirm modal, pagination, empty states)

### Changed
* `routes/web.php` — full permission-gated pond + pond-type route group
  (literal `types`/`status` segments declared before the `{pond}` wildcard)
* `config/navigation.php` — Pond Management group uses the granular
  `pond_type.*` keys
* `config/fishfarm.php` — `modules.ponds` flag set to `true`

### Fixed (found during Phase 2 verification)
* `Pond::scopeSearch()` called `Str::escapeLike()`, which does not exist in
  Laravel 12 — **every pond search returned HTTP 500**. Replaced with an inline
  LIKE-wildcard escape (`\`, `%`, `_`).
* `UpdatePondTypeRequest` read the route key `pond_type`, but the route parameter
  is `{pondType}` — the bound model was always `null`, so editing a pond type and
  saving an **unchanged** name failed with a false "name already exists" error.
  Corrected to `$this->route('pondType')`.
* Three Phase 2 pages (`ponds/show`, `ponds/edit`, `ponds/status`) were missing
  the `grid` display class on their layout wrapper, collapsing the intended
  multi-column layout. Added `grid`.

### Docs
* Updated `PROJECT.md`, `ARCHITECTURE.md`, `DATABASE.md`, `BUSINESS_LOGIC.md`,
  `ROUTES.md`, `MODULES.md`, `PERMISSIONS.md`, `DEVELOPMENT_WORKFLOW.md`,
  `CHANGELOG.md` to reflect the implementation.

### Not done (deliberately)
* No fish stock, feed, growth, inspection, mortality, harvest or financial logic
  was added. The pond details page shows honest empty states for those areas.
* No tests were added (per the project workflow).
* Phase 3 (Fish Stock) was **not** started.

## 2026-09-21 (c) — PHASE 1: Authentication + Company + Users + Roles + Permissions

Implemented the access-control foundation. **No business module was built** (no
ponds, fish, feed, FCR, sales, suppliers, parties or reports), and no fake
business data was created.

### Added

**Authentication (Laravel built-in — no external auth package)**
* `app/Http/Controllers/Auth/LoginController.php` — login, logout, session
  regeneration, rate limiting (5/min per email+IP), inactive-account refusal
* `app/Http/Requests/Auth/LoginRequest.php` — validation + `remember()`
* `resources/views/auth/login.blade.php` — uses the existing auth layout
* `routes/auth.php` — `login`, `login.store`, `logout`

**Authorization middleware (the reusable enforcement mechanism)**
* `app/Http/Middleware/EnsurePermission.php` — alias `permission`; accepts
  multiple keys, redirects guests to `/login`, returns 403 for a
  permission-less user, and logs out deactivated users mid-session

**Company**
* `app/Http/Controllers/Settings/CompanyController.php`
* `app/Http/Requests/Settings/UpdateCompanyRequest.php`
* `app/Services/Settings/CompanyService.php` — transactional write + logo
  file handling (replaces/removes the old file)
* `resources/views/settings/company/edit.blade.php`
* `app/Support/CompanyContext.php` — the ONE place that resolves "the company"
  (cached; the seam a future multi-company version would change)
* `app/View/Composers/CompanyComposer.php` — shares company name/logo/initials
  with the layouts so branding is never hard-coded

**Users**
* `app/Http/Controllers/Settings/UserController.php` — index (search, status
  and role filters, pagination), create, store, edit, update, toggleActive, destroy
* `app/Http/Requests/Settings/StoreUserRequest.php`
* `app/Http/Requests/Settings/UpdateUserRequest.php` — blocks self role-change
  and self-deactivation
* `app/Services/Settings/UserService.php` — transactional create/update with
  role sync; refuses self-delete and deleting the last active Super Admin
* `resources/views/settings/users/{index,create,edit,_form}.blade.php`

**Roles & Permissions**
* `app/Http/Controllers/Settings/RoleController.php`
* `app/Http/Controllers/Settings/PermissionController.php`
* `app/Http/Requests/Settings/{Store,Update}RoleRequest.php`
* `app/Services/Settings/RoleService.php` — transactional role + grant sync;
  refuses deleting system roles or roles still assigned to users
* `resources/views/settings/roles/{index,create,edit}.blade.php`
* `resources/views/settings/permissions/index.blade.php`
* `resources/views/components/form/permission-matrix.blade.php` — grouped,
  accessible matrix with per-group "select all"
* `resources/js/components/permission-matrix.js`

**Profile**
* `app/Http/Controllers/Settings/ProfileController.php`
* `app/Http/Requests/Settings/UpdateProfileRequest.php` — accepts only
  name/email/password; requires the current password to change it
* `resources/views/settings/profile/edit.blade.php`

### Changed
* `config/permissions.php` — added the foundation groups **Company, Users,
  Roles, Permissions, Settings**; **removed** the catch-all `users.manage` /
  `roles.manage` / `settings.manage` / `farm.manage` keys in favour of
  per-action keys. Rebalanced `farm_admin` (now 66, without `roles.*` /
  `permissions.manage`, matching its own description).
* `config/navigation.php` — every menu item now declares a `permission`; the
  Settings group points at the real Phase 1 routes.
* `resources/views/components/sidebar/sidebar.blade.php` — permission-aware
  filtering (a group disappears when the user can see none of its children) and
  **dynamic company name/logo** in the brand block.
* `resources/js/app.js` — registers the permission-matrix module.
* `bootstrap/app.php` — registers the `permission` middleware alias and sets
  `redirectGuestsTo(route('login'))`.
* `app/Providers/AppServiceProvider.php` — `Gate::before()` Super Admin
  bypass, `@permission` / `@role` Blade directives, and the company view composer.
* `routes/web.php` — dashboard and **all** module routes now carry
  `permission:` middleware; the settings placeholder block was replaced by the
  real Company/Users/Roles/Permissions/Profile routes.
* `database/seeders/DatabaseSeeder.php` — seeds permissions → roles → admin.
* `.env` / `.env.example` — added `ADMIN_NAME` / `ADMIN_EMAIL` /
  `ADMIN_PASSWORD` and `COMPANY_*` (used once by `AdminUserSeeder`).

### Fixed
* `UpdateCompanyRequest` — a stray quote produced a PHP syntax error in the
  `logo` rule (introduced during an interrupted write; detected by lint and fixed).
* `RoleService` — corrected the namespace from `App\Http\Services` to
  `App\Services\Settings` so it matches its directory and the project convention.
* `farm_admin` was inadvertently granted all 70 permissions (including
  `roles.delete`) contradicting its own description; corrected to 66.

### Database
* **Migrations created (incremental, reversible):**
  * `0001_01_01_000003_create_companies_table.php`
  * `0001_01_01_000004_add_company_id_and_is_active_to_users_table.php`
  * `0001_01_01_000005_create_roles_and_permissions_tables.php`
* **Migrations were RUN** against MySQL `fish_farm_erp` (created with
  `IF NOT EXISTS`). **No destructive command was used** — no
  `migrate:fresh`, `migrate:refresh` or `db:wipe`. The `users` change is
  an `ALTER TABLE`, not a rebuild.
* **Seeders created:** `PermissionSeeder` (70 permissions), `RoleSeeder`
  (7 roles), `AdminUserSeeder` (company + first Super Admin). All idempotent —
  verified by re-running: still 1 company / 1 user / 7 roles / 70 permissions,
  no duplicates.
* **No business tables and no business data.** Post-verification state is exactly
  the setup data above.

### Routes
* **Added (22):** `login`, `login.store`, `logout`; `settings.company.edit`,
  `settings.company.update`; `settings.profile.edit`, `settings.profile.update`;
  `settings.users.index` / `create` / `store` / `edit` / `update` /
  `toggle-active` / `destroy`; `settings.roles.index` / `create` /
  `store` / `edit` / `update` / `destroy`; `settings.permissions.index`.
* **Changed:** `dashboard` moved inside the auth group and now requires
  `permission:dashboard.view`. All `/fish-farm/*` routes now carry their
  matching `permission:` middleware (they still resolve to `PendingController`).
* **Removed:** the `settings.farm` and `settings.system` placeholders
  (replaced by `settings.company.*` and `settings.permissions.index`).
* Total: **74 routes** (was 58).

### Permissions
* **New keys (22):** `company.view`, `company.update`; `users.view`,
  `users.create`, `users.update`, `users.delete`; `roles.view`,
  `roles.create`, `roles.update`, `roles.delete`; `permissions.view`,
  `permissions.manage`; `settings.view`, `settings.update`; plus
  `dashboard.view` (now enforced) and `growth.view` / `growth.create`.
* **Removed keys:** `users.manage`, `roles.manage`, `settings.manage`,
  `farm.manage` — replaced by the granular keys above.
* **Catalogue now 70 permissions across 17 groups.**
* **7 seeded roles:** super_admin (70), farm_admin (66), accountant (28),
  manager (29), viewer (14), farm_staff (11), sales_staff (10).
* **Enforcement live:** `permission:` middleware on every business and settings
  route; `Gate::before()` Super Admin bypass is the only bypass.

### Notes
* **Version 1 uses a single-company architecture. Multiple users operate within
  one company/farm. Multi-company / multi-tenant support is intentionally not
  implemented.** No tenant middleware, no tenant switching, no company selector,
  no `farm_id` columns on business tables.
* **Verification performed (no formal test suite created):**
  * App boots; `php artisan route:list` reports 74 routes with no errors.
  * All PHP files lint clean; **all Blade templates compile**.
  * `npm run build` succeeds.
  * **Guest access:** every protected route redirects to `/login`.
  * **Authorization:** an authenticated Viewer gets **200** on
    `/dashboard`, `/settings/company` and `/settings/profile`; **403** on
    `/settings/users`, `/settings/users/create`, `/settings/roles` and
    `/settings/permissions`.
  * **CRUD (through the real services):** user create → update → toggle →
    delete ✓; role create → update grants → delete ✓; deleting a **system role**
    blocked ✓; deleting a **role in use** blocked ✓; **self-deactivate** and
    **self-delete** blocked ✓.
  * **Sidebar:** verified permission-aware per role — Farm Staff, Sales Staff and
    Viewer each see only their permitted groups.
  * **Browser:** login page renders with CSRF token and dynamic company branding;
    login succeeds and redirects to the dashboard; **0 console errors, 0 failed
    requests** on the login page and dashboard.
  * **Temporary verification records were removed** — the database holds only the
    seeded setup data.
  * **PWA foundation, design tokens, sidebar groups and all 11 docs remain intact.**
* **Environment findings (not project defects):** `php` and `git` are not on
  `PATH` on this machine — use `C:\xampp\php\php.exe`; Git is unavailable, so
  no commit was possible or attempted. The single-threaded PHP dev server is
  very slow (30s+ per page); set `PHP_CLI_SERVER_WORKERS` for local browsing.
* **Deferred to later phases:** policies for business models, password reset,
  email verification, the `settings` key/value table, and every business module.

---
Format for each entry:

```markdown
## YYYY-MM-DD
### Added
* ...

### Changed
* ...

### Fixed
* ...

### Database
* ...

### Routes
* ...

### Permissions
* ...

### Notes
* ...
```

Keep this updated after meaningful architectural or module changes.

---

## 2026-09-21

Architecture, documentation and design-system foundation. No business module
was implemented, and no fake data was introduced anywhere.

### Added

**Documentation system (docs/)**
* docs/PROJECT.md — project overview, stack, rules, restrictions
* docs/ARCHITECTURE.md — responsibility matrix and folder rationale
* docs/DATABASE.md — current schema + target entity documentation
* docs/BUSINESS_LOGIC.md — authoritative FCR, stock and financial formulas
* docs/UI_GUIDELINES.md — colour tokens, gradients, component library
* docs/ROUTES.md — route layout, naming conventions, conventions for writes
* docs/PWA.md — PWA capabilities, offline limits, future work
* docs/MODULES.md — per-module reference with implementation status
* docs/PERMISSIONS.md — roles, permission keys, isolation requirements
* docs/DEVELOPMENT_WORKFLOW.md — before/during/after work process
* docs/CHANGELOG.md — this file

**UI design system**
* esources/css/app.css — Tailwind v4 `@theme` design tokens (brand,
  semantic, surface, text, sidebar colours; radii; shadows; layout metrics),
  base layer, component layer (`.gradient-primary`, `.gradient-hero`,
  `.surface-card`, `.table-shell`, `.table-actions`) and utilities
* esources/views/components/layout/app.blade.php — application shell
* esources/views/components/layout/auth.blade.php — split-screen auth shell
* esources/views/components/layout/error.blade.php — error card
* esources/views/components/layout/page-header.blade.php — gradient hero band
* esources/views/components/layout/module-pending.blade.php — pending panel
* esources/views/components/layout/network-indicator.blade.php — online pill
* esources/views/components/layout/network-status.blade.php — offline banner
* esources/views/components/sidebar/sidebar.blade.php — navigation
* esources/views/components/sidebar/icon.blade.php — inline SVG icon set
* esources/views/components/header/header.blade.php — sticky header
* esources/views/components/breadcrumb/breadcrumb.blade.php
* esources/views/components/card/card.blade.php
* esources/views/components/kpi-card/kpi-card.blade.php
* esources/views/components/table/table.blade.php
* esources/views/components/pagination/pagination.blade.php
* esources/views/components/button/button.blade.php
* esources/views/components/badge/badge.blade.php
* esources/views/components/alert/alert.blade.php
* esources/views/components/toast/toast-stack.blade.php
* esources/views/components/confirm-modal/confirm-modal.blade.php
* esources/views/components/form/{field,input,select,textarea,date-picker}.blade.php
* esources/views/components/empty-state/empty-state.blade.php
* esources/views/components/loading/loading.blade.php

**JavaScript (vanilla, no framework)**
* esources/js/app.js — bootstraps all modules
* esources/js/network.js — online/offline awareness
* esources/js/pwa.js — service worker registration + safe update detection
* esources/js/components/ui.js — declarative UI helper (no Alpine dependency)
* esources/js/components/sidebar.js — drawer store
* esources/js/components/toast.js — flash-message behaviour
* esources/js/components/confirm-modal.js — replaces `confirm()`
* esources/js/components/form-guards.js — UX hints + offline write guard

**PWA foundation**
* public/manifest.webmanifest — identity, icons, shortcuts, standalone
* public/sw.js — service worker: cache-first assets, network-first
  navigations, offline fallback; never caches authenticated responses or non-GET
* public/offline.html — self-contained offline fallback page
* public/icons/{icon-192,icon-512,maskable-512,apple-touch-icon}.png
* 	ools/generate-icons.php — regenerates icons from the brand gradient

**Business-logic service foundations**
* pp/Services/Fcr/FcrCalculator.php — FCR with 5 explicit edge-case states
* pp/Services/Fcr/FcrResult.php — immutable result value object
* pp/Services/Fish/FishStockService.php — fish stock movement rules
* pp/Services/Feed/FeedStockService.php — feed stock movement rules
* pp/Services/Finance/LedgerRules.php — balance/due/profit sign conventions
* pp/Services/Dashboard/DashboardMetricsService.php — dashboard aggregation
* pp/Support/Metric.php — metric value object (real vs pending)
* pp/Support/Money.php — currency formatting
* pp/Support/Dates.php — date formatting

**Application structure**
* pp/Http/Controllers/Dashboard/DashboardController.php
* pp/Http/Controllers/Settings/PendingController.php — honest placeholder
* config/navigation.php — single source of truth for the sidebar menu
* config/fishfarm.php — version, currency, pagination, PWA, module flags
* esources/views/dashboard/index.blade.php — real dashboard shell
* esources/views/placeholders/module.blade.php — not-implemented page
* outes/auth.php — designated home for authentication routes
* Domain folders under `app/Http/Controllers`, `app/Services`, and
  `resources/views` (Pond, Feed, Fish, Fcr, Sales, Customer, Supplier, Party,
  Finance, Reports, Settings, Admin)
* `docs/` created as the permanent documentation root

### Changed
* `.env` / `.env.example` — `APP_NAME="Fish Farm ERP"`, `APP_URL` set
  for the XAMPP path, `DB_CONNECTION=mysql` with `DB_DATABASE=fish_farm_erp`
* `.env` / `.env.example` — session/cache/queue switched from `database`
  to `file`/`sync` so the app boots before MySQL is started (see Notes)
* `routes/web.php` — replaced the stock welcome route with the full module
  route layout (named groups under `/fish-farm/*` and `/settings/*`)
* `bootstrap/app.php` — now loads `routes/auth.php` alongside `web.php`;
  documented where the `permission` middleware alias will be registered
* `resources/css/app.css` — replaced the stock Tailwind theme with the ERP
  design-token system
* `resources/views/layouts/*` — moved to
  `resources/views/components/layout/*` so they are usable as
  `<x-layout.app>` anonymous components
* `resources/js/app.js` — replaced the bare `bootstrap` import with the
  layered bootstrap

### Fixed
* Dashboard grid containers were missing the base `grid` utility class,
  causing KPI and analytics blocks to stack vertically instead of forming a
  responsive grid. Verified visually after the fix.
* Corrected a malformed pagination expression
  (`()` → `->currentPage()`).
* Removed a leading blank line that produced a PHP syntax error in
  `app/Services/Feed/FeedStockService.php`.

### Database
* **No ERP tables created. No migration was run.**
* Existing migrations are unchanged stock Laravel files:
  `0001_01_01_000_create_users_table.php` (users, password_reset_tokens,
  sessions), `..._000001_create_cache_table.php`, `..._000002_create_jobs_table.php`
* **No destructive operation was performed** — no `migrate:fresh`,
  `migrate:refresh` or `db:wipe`.
* The full target entity list and per-entity documentation were added to
  `docs/DATABASE.md`; the `fish_farm_erp` MySQL database still needs to be
  created before migrations can run.

### Routes
* Added the complete module route layout — 58 routes total, all named:
  `dashboard`, `offline`, and module groups `ponds.*`, `ledger.*`,
  `feed.*, `fcr.*`, `fish.*`, `sales.*`, `customers.*`,
  `suppliers.*`, `parties.*`, `finance.*`, `reports.*`, `settings.*`
* `/` now redirects to `/dashboard`
* All `/fish-farm/*` and `/settings/*` routes sit inside an `auth`
  middleware group
* Unimplemented module routes resolve to `PendingController`, which renders an
  honest "not implemented" page — no route displays fabricated data
* Verified with `php artisan route:list` — no errors

### Permissions
* Permission architecture documented in `docs/PERMISSIONS.md` (no database
  tables yet):
  * 7 intended roles: Super Admin, Farm Admin, Manager, Accountant, Farm Staff,
    Sales Staff, Viewer
  * Granular permission keys per module (`pond.view`, `pond.create`,
    `feed.purchase`, `fish.harvest`, `sales.create`, `reports.view`,
    `users.manage`, `settings.manage`, …)
  * Three-layer enforcement: route middleware + policy + farm-scoped query
  * Farm-level data isolation; never trust an ID from the URL
* Sidebar/header reference `route('login')`/`route('logout')` defensively
  via `Route::has()` so the UI works before authentication exists

### Notes
* **This was the architecture and documentation phase only.** No ERP business
  module was implemented, by design. Every module is at "scaffolded" status —
  see `docs/MODULES.md`.
* **No fake data.** Dashboard KPIs render `—` with an explanatory note until
  their modules exist. `App\Support\Metric` has an explicit "pending" state so
  a missing data source can never be mistaken for a zero.
* **Environment prerequisite:** MySQL/MariaDB must be started in XAMPP and a
  `fish_farm_erp` database created before running `php artisan migrate`.
  Session/cache/queue were set to `file`/`sync` so the app boots without it;
  switch them back to `database` if desired once the DB exists.
* The PHM `zip` extension was enabled in `C:\xampp\php\php.ini` (backup at
  `php.ini.bak-fisherp`) because Composer required it.
* Verified end-to-end: `npm run build` succeeds, the dashboard renders HTTP 200
  in a real browser with **0 console errors** and **0 failed requests**, showing
  10 KPI cards, a 10-group / 56-link sidebar, and the network indicator.
* Decisions needing user confirmation are listed in the handover report:
  farm/tenancy model, authentication implementation, and whether to keep the
  `database` session/cache drivers.

## 2026-09-21 (b)

**Architecture decision: single company for Version 1.** The application is
explicitly NOT multi-tenant. Implemented the identity and access foundation:
Company -> Users -> Roles & Permissions. No ERP business module was built, and
no destructive database command was run.

### Added

**Configuration (single source of truth)**
* `config/permissions.php` — the permission catalogue (61 keys across 12
  groups) and the 7 seeded roles with their grants. Consumed by the seeders and
  the future Roles & Permissions UI.
* `config/fishfarm.php` — new `company` and `admin` seed-default blocks
  driven by env vars; no credentials hard-coded.

**Migrations (incremental, reversible)**
* `0001_01_01_000003_create_companies_table.php` — `companies`
* `0001_01_01_000004_add_company_id_and_is_active_to_users_table.php` —
  alters the existing `users` table (adds `company_id` FK + `is_active`);
  does NOT recreate the table and drops no data
* `0001_01_01_000005_create_roles_and_permissions_tables.php` — `roles`,
  `permissions`, `permission_role`, `role_user`

**Models**
* `app/Models/Company.php` — single business identity; `current()` helper
  centralises "the one company" lookup; `users()` relationship
* `app/Models/Role.php` — `permissions()`/`users()`, `isDeletable()`
  (system roles protected), `grant()` helper
* `app/Models/Permission.php` — `roles()`

**Seeders**
* `database/seeders/PermissionSeeder.php` — idempotent catalogue seeding
* `database/seeders/RoleSeeder.php` — idempotent roles + `sync()` of grants
  (removing a permission from config and re-seeding actually revokes it)
* `database/seeders/AdminUserSeeder.php` — creates the single company and the
  first Super Admin. Credentials come from env; a random password is generated
  and printed if `ADMIN_PASSWORD` is unset.

**Environment**
* `.env` / `.env.example` — `APP_TIMEZONE`, `COMPANY_*` and `ADMIN_*`
  variables.

### Changed

* `app/Models/User.php` — added `company_id`/`is_active` to fillable and
  casts; added `company()` and `roles()` relationships; added
  `permissionNames()`, `hasPermission()`, `hasRole()`, `hasAnyRole()`
  and `isActive()` helpers. Permissions are resolved per request via `once()`
  to avoid repeated queries.
* `database/seeders/DatabaseSeeder.php` — **removed the stock
  `test@example.com` user** (it was fake data) and replaced it with the ordered
  setup seeders: permissions -> roles -> admin.
* `docs/PROJECT.md` §7 — replaced "every business record is scoped to the
  farm/company context" with the single-company rule and the explicit
  prohibition on `farm_id` columns.
* `docs/PROJECT.md` §11 rule 6 — authorization wording updated (no tenant
  scoping).
* `docs/ARCHITECTURE.md` §5 — "Farm/company scoping on every business table"
  replaced with the single-company relationship strategy.
* `docs/PERMISSIONS.md` §3 — rewritten from "User data isolation" (farm-scoped)
  to "Access control (single company)": no tenant boundary, no `BelongsToFarm`
  trait, no global scope; authorization via route `permission:` middleware and
  policies instead.
* `docs/PERMISSIONS.md` §9 — anti-pattern updated to reference route model
  binding + policy rather than a farm-scoped query.
* `docs/MODULES.md` — Settings module rule changed from "per-farm key/group/
  value rows" to the single company.
* `docs/DEVELOPMENT_WORKFLOW.md` — "during work" rule 7 updated to state
  Version 1 is single-company and tenant scoping must not be added.

### Fixed

* None. (No defects were found in the existing UI or PWA foundation while making
  this change; neither was modified.)

### Database

* **New tables:** `companies`, `roles`, `permissions`, `permission_role`,
  `role_user`.
* **Altered table:** `users` — added `company_id` (nullable FK ->
  `companies`, cascade on delete) and `is_active` (boolean, default true).
* **No business/ERP tables created.** No `farm_id` column was added anywhere.
* **No destructive command was run** — no `migrate:fresh`,
  `migrate:refresh` or `db:wipe`.
* **No migration has been run against MySQL.** The `fish_farm_erp` database
  still needs to be created, then `php artisan migrate` and
  `php artisan db:seed`.
* Migrations were validated end-to-end against a **throwaway SQLite file** that
  was deleted immediately afterwards; MySQL and all real data were untouched.

### Routes

* **No routes added or changed.** The existing 58 named routes are unchanged.
  Authentication routes remain unimplemented in `routes/auth.php` (Laravel-based
  auth is the planned approach; no fake login was added).

### Permissions

* **New permission catalogue (61 keys)** across 12 groups: Dashboard, Pond, Feed,
  Fish Stock, FCR & Growth, Sales, Customers, Suppliers, Party, Finance, Reports,
  Administration. Full list in `docs/PERMISSIONS.md` §5 and
  `config/permissions.php`.
* **New permission keys** beyond the original plan:
  `dashboard.view`, `company.manage`, `growth.view`, `growth.create`,
  `notifications.manage`.
* **7 seeded roles:** `super_admin` (all 61), `farm_admin` (58), `manager`
  (28), `accountant` (30), `farm_staff` (11), `sales_staff` (10),
  `viewer` (13). All are `is_system = true` and protected from deletion.
* **Not implemented:** no `permission` middleware alias, no policies, no login
  enforcement. `User::hasPermission()` is in place ready for them.
* `super_admin` is the ONLY role shortcut in the codebase — every other role is
  defined purely by its permission list.

### Notes

* **Single company (Version 1).** One `companies` row is the business identity.
  Only `users` references it. No tenant middleware, no tenant switching, no
  company selector, no multi-tenant permissions, no SaaS billing. The migration
  path to multi-company is documented in `docs/DATABASE.md` §1 and left open,
  but deliberately not implemented.
* **Future-proofing without complexity:** `Company::current()` is the single
  place that resolves "the company", so a future multi-company version has one
  seam to change instead of a scattering of lookups.
* **No fake data.** The stock `test@example.com` user was removed. Seeding
  creates setup data only (company, roles, permissions, one admin) — no ponds,
  feed, stock, sales or transactions.
* **UI and PWA foundations were not touched.** The documentation system, all
  Blade components, the design tokens and the service worker remain exactly as
  they were.
* **Verified:** all PHP lints clean; all 6 migrations apply in order; seeders
  produce 1 company / 1 user / 7 roles / 61 permissions; the relationship chain
  (user -> company, user -> roles, `hasPermission`) resolves correctly; routes
  still load and all Blade templates compile.