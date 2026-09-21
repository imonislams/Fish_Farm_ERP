# Architecture — Fish Farm ERP

This document defines **what belongs where**. Read `docs/PROJECT.md` first.

---

## 1. Laravel architecture

A conventional Laravel 12 application organised by **business domain**, not by
technical type. The goal is small, focused classes instead of large controllers.

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php          base (tests only; keep thin)
│   │   ├── Dashboard/
│   │   ├── Pond/
│   │   ├── Feed/
│   │   ├── Fish/
│   │   ├── Fcr/
│   │   ├── Sales/
│   │   ├── Customer/
│   │   ├── Supplier/
│   │   ├── Party/
│   │   ├── Finance/
│   │   ├── Reports/
│   │   ├── Settings/
│   │   └── Admin/
│   ├── Requests/                   FormRequest validation
│   └── Middleware/                 cross-cutting request concerns
├── Models/                         Eloquent models
├── Services/
│   ├── Dashboard/
│   ├── Pond/
│   ├── Feed/
│   ├── Fish/
│   ├── Fcr/                        FcrCalculator, FcrResult
│   ├── Sales/
│   ├── Finance/                    LedgerRules
│   ├── Reports/
│   └── Pwa/
├── Policies/                       authorization
├── Support/                        Metric, Money, Dates
└── Providers/

resources/
├── views/
│   ├── components/                 reusable Blade components
│   ├── layouts/                    (legacy location — see note in UI_GUIDELINES)
│   ├── dashboard/  ponds/  feed/  fish/  fcr/  sales/
│   ├── customers/  suppliers/  parties/  finance/
│   ├── reports/  settings/  admin/  auth/  errors/
│   └── placeholders/               honest "not implemented" pages
├── css/app.css                     design tokens + component/utility layers
└── js/
    ├── app.js                      bootstraps everything
    ├── network.js  pwa.js
    └── components/                 ui.js, sidebar.js, toast.js,
                                    confirm-modal.js, form-guards.js

routes/
├── web.php                         application routes (module groups)
├── auth.php                        authentication routes
└── console.php                     console commands
```

## 2. Folder structure rationale

Folders are named after **domains** the business recognises (Pond, Feed, FCR,
Sales). A developer looking for feed-stock rules should find them in
`app/Services/Feed/` without searching. Controllers, requests, services and views
mirror the same domain list so a module is self-describing.

## 3. Responsibility matrix

### Controller — `app/Http/Controllers/<Domain>/`
**Belongs:** handle the request, authorize, validate (via FormRequest), call one
or more services, return a view or redirect with a flash message.
**Does NOT belong:** calculations, query building beyond trivial lookups, stock
maths, ledger maths, direct multi-table writes.
**Rule of thumb:** if a controller method is longer than roughly 20 lines of
logic, that logic belongs in a service.

### Service — `app/Services/<Domain>/`
**Belongs:** business logic, calculations, transactions
(`DB::transaction()`), stock adjustments, ledger calculations, report
aggregation, dashboard metrics.
**Does NOT belong:** HTTP concerns (requests, responses, redirects, sessions).
**Rule:** calculations are defined **once** here, never duplicated in a
controller, another service or a Blade file.

Key services (existing or planned):

| Service                     | Responsibility                              |
| --------------------------- | ------------------------------------------- |
| `DashboardMetricsService`   | dashboard KPI/analytics aggregation          |
| `Fcr\FcrCalculator`         | FCR formula + edge cases (implemented)       |
| `Fcr\FcrResult`             | immutable FCR result value object            |
| `Fish\FishStockService`     | fish stock movement rules                    |
| `Feed\FeedStockService`     | feed stock movement rules                    |
| `Sales\SalesService`        | sale creation, items, stock + ledger effects |
| `Finance\LedgerRules`       | balance/due/profit sign conventions          |
| `Finance\CustomerBalanceService` | customer dues                          |
| `Finance\SupplierBalanceService` | supplier dues                          |
| `Pond\PondLedgerService`    | pond ledger entries                          |
| `Reports\ReportService`     | reusable report query + aggregation          |

### Model — `app/Models/`
**Belongs:** relationships, casts, accessors/mutators, scopes, small
model-level behaviour.
**Does NOT belong:** multi-record orchestration, cross-aggregate calculations,
HTTP concerns.

### Request — `app/Http/Requests/`
**Belongs:** validation rules, `authorize()`.
**Rule:** every important write operation gets a FormRequest. Validation is
server-side and authoritative — client-side validation is a UX extra only.

### Policy — `app/Policies/`
**Belongs:** "may this user act on this record?" decisions.
**Rule:** authorization is enforced here (and at route level), not by hiding
menu items.

### Middleware — `app/Http/Middleware/`
**Belongs:** genuinely cross-cutting concerns (`permission:users.create`, rate
limiting). Register aliases in `bootstrap/app.php`.

Implemented: `EnsurePermission` (alias `permission`). It denies by default,
redirects guests to `/login`, returns 403 for an authenticated user lacking the
permission, and rejects **deactivated** users mid-session. There is deliberately
**no tenant middleware** — Version 1 is single-company (docs/DATABASE.md §1).

### Service — write-path guards
The service layer is the last line of defence, because it is the actual write
path. Guards that must not live only in the UI:

| Service       | Guard                                                    |
| ------------- | -------------------------------------------------------- |
| `UserService` | cannot deactivate/delete yourself; cannot delete the last active Super Admin; role sync is transactional |
| `RoleService` | cannot delete a system role; cannot delete a role still assigned to users |
| `CompanyService` | logo replace/remove deletes the old file in the same transaction |

### Blade — `resources/views/**`
**Belongs:** presentation only — layout, loops, conditionals, `number_format`
style formatting.
**Does NOT belong:** business calculations, database queries, FCR/stock/ledger
maths. If a value needs computing, the controller passes it in already computed
by a service.

### JavaScript — `resources/js/**`
**Belongs:** UI interactions only — drawer open/close, toasts, confirmations,
network-status display, progressive enhancement.
**Does NOT belong:** business logic, data shaping, validation authority.

### CSS — `resources/css/app.css`
**Belongs:** design tokens (`@theme`) and genuinely shared component classes.
**Does NOT belong:** one-off page styling. Page-specific styling uses utility
classes directly in the Blade file.

## 4. Route organisation

Module route groups with a consistent naming prefix:

```php
Route::prefix('fish-farm/ponds')->name('ponds.')->group(function () {
    Route::get('/', [PondController::class, 'index'])->name('index');
    Route::get('/create', [PondController::class, 'create'])->name('create');
});
```

Rules:
- Every route has a name.
- Blade uses `route('ponds.index')`, never a hard-coded URL.
- Route names follow the module prefix so sidebar active-state detection works
  with `request()->routeIs('ponds.*')`.
- Public ID-style identifiers must never be trusted for authorization.

See `docs/ROUTES.md`.

## 5. Database relationship strategy

- Explicit foreign keys with sensible `onDelete` behaviour.
- **Single-company model:** only `users` belongs to `companies`. Business tables
  reference their domain parent (a pond, a sale, a customer) — **not** a company
  column. Adding `farm_id`/`company_id` everywhere is explicitly rejected for
  Version 1 (see `docs/DATABASE.md` §1).
- Relationship methods named for the domain vocabulary (`ponds()`, `stockings()`,
  `usages()`).
- Avoid N+1: eager-load relationships the view iterates (`with([...])`).
- Index columns used in `where`, joins, and report grouping.
- Transactions for multi-record writes.

## 6. Transactions

Use `DB::transaction()` whenever several related records must change together —
for example a sale creating the sale, its items, a stock movement, a customer
balance effect and a ledger entry. These must never leave the database
half-updated.

```php
DB::transaction(function () use ($data) {
    // create related records atomically
});
```

## 7. Validation

Server-side validation is authoritative. FormRequests for important forms.
Validate: required fields, numeric values, positive quantities, valid dates,
valid relationships (`exists:...`), stock availability, financial amounts.
Never rely solely on JavaScript.

## 8. Security

CSRF protection, authentication, authorization, validation, mass-assignment
protection (`$fillable`), escaped output (Blade `{{ }}`), safe queries (Eloquent
bindings), rate limiting on sensitive endpoints, and no trusting user IDs.

## 9. Performance

Eager loading, pagination, indexed columns, reusable services, no repeated
expensive queries, no huge Blade files, no unnecessary JavaScript. Dashboard
queries are intentionally designed rather than improvised.

## 10. Adding a new module — checklist

1. Read `docs/PROJECT.md`, `ARCHITECTURE.md`, `DATABASE.md`, `BUSINESS_LOGIC.md`,
   `PERMISSIONS.md`.
2. Check `docs/DATABASE.md` for existing equivalent tables — do not duplicate.
3. Add migrations (incremental, reversible).
4. Add models + relationships + casts.
5. Add services for all calculations and multi-record writes.
6. Add FormRequests for validation.
7. Add policies + register permission checks.
8. Add thin controllers.
9. Add Blade views **using existing components**.
10. Add named routes under the module prefix.
11. Flip the module flag in `config/fishfarm.php`.
12. Update `docs/MODULES.md`, `docs/CHANGELOG.md`, and any affected doc.