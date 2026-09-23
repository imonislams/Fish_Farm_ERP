# Routes — Fish Farm ERP

Read `docs/PROJECT.md` and `docs/ARCHITECTURE.md` first.

Route files: `routes/web.php` (application), `routes/auth.php` (authentication),
`routes/console.php` (console commands). Both web files are registered in
`bootstrap/app.php`.

---

## 1. Rules

1. **Every route has a name.** React resolves URLs from the shared `routes` prop
   (`props.routes['ponds.index']`); Blade uses `route('name')`. Neither hard-codes a URL.
2. Names follow the module prefix: `ponds.index`, `ponds.create`, `fcr.growth`.
3. Route groups use `Route::prefix(...)->name('module.')`.
4. Literal segments (`/create`, `/export`, `/ledger`, `/types`, `/status`) are
   declared **before** any `{wildcard}` so they are never captured by model binding.
5. Every module route carries `permission:*` middleware, and the controller also
   re-asserts the policy (`Gate::authorize`), so an id in the URL cannot reach a
   record the user is not permitted to act on. Every module is implemented and
   returns an Inertia/React page — **nothing fakes data**.
6. `route('login')` / `route('logout')` are referenced defensively with
   `Route::has()` so the UI works before auth routes exist.

## 2. Layout

```
/                                   → redirect to /dashboard
/dashboard                          → dashboard
/offline                            → offline (PWA fallback reference)

/fish-farm/ponds                    → ponds.index
/fish-farm/ponds/create             → ponds.create
/fish-farm/ponds/status             → ponds.status
/fish-farm/ponds/types              → ponds.types.index
/fish-farm/ponds/types/create       → ponds.types.create
/fish-farm/ponds/types/{pondType}/edit → ponds.types.edit

/fish-farm/pond-ledger              → ledger.index
/fish-farm/pond-ledger/transactions → ledger.transactions
/fish-farm/pond-ledger/transactions/create → ledger.transactions.create

/fish-farm/feed                     → feed.index
/fish-farm/feed/stock               → feed.stock
/fish-farm/feed/types               → feed.types.index
/fish-farm/feed/types/create        → feed.types.create
/fish-farm/feed/types/{feedType}/edit → feed.types.edit
/fish-farm/feed/purchases           → feed.purchases.index
/fish-farm/feed/purchases/create    → feed.purchases.create
/fish-farm/feed/usages              → feed.usages.index
/fish-farm/feed/usages/create       → feed.usages.create
/fish-farm/feed/adjustments         → feed.adjustments.index
/fish-farm/feed/adjustments/create  → feed.adjustments.create

/fish-farm/fcr                      → fcr.index
/fish-farm/fcr/inspections          → fcr.inspections.index
/fish-farm/fcr/inspections/new      → fcr.inspections.create
/fish-farm/fcr/growth               → fcr.growth
/fish-farm/fcr/feed-growth          → fcr.feed-growth
/fish-farm/fcr/comparison           → fcr.comparison
/fish-farm/fcr/schedules            → fcr.schedules.index
/fish-farm/fcr/reports              → fcr.reports

/fish-farm/fish                     → fish.index
/fish-farm/fish/species             → fish.species.index
/fish-farm/fish/species/create      → fish.species.create
/fish-farm/fish/species/{species}/edit → fish.species.edit
/fish-farm/fish/stockings           → fish.stockings.index
/fish-farm/fish/stockings/create    → fish.stockings.create
/fish-farm/fish/mortalities         → fish.mortalities.index
/fish-farm/fish/mortalities/create  → fish.mortalities.create
/fish-farm/fish/harvests            → fish.harvests.index
/fish-farm/fish/harvests/create     → fish.harvests.create

/fish-farm/sales                    → sales.index
/fish-farm/sales/list               → sales.list
/fish-farm/sales/ledger             → sales.ledger

/fish-farm/customers                → customers.index
/fish-farm/customers/payments       → customers.payments.index
/fish-farm/customers/dues           → customers.dues

/fish-farm/suppliers                → suppliers.index
/fish-farm/suppliers/create         → suppliers.create
/fish-farm/suppliers/purchases      → suppliers.purchases.index
/fish-farm/suppliers/payments       → suppliers.payments.index
/fish-farm/suppliers/dues           → suppliers.dues

/fish-farm/parties                  → parties.index
/fish-farm/parties/create           → parties.create
/fish-farm/parties/transactions     → parties.transactions
/fish-farm/parties/ledger           → parties.ledger

/fish-farm/finance/income           → finance.income.index
/fish-farm/finance/expenses         → finance.expenses.index

/fish-farm/reports/sales            → reports.sales
/fish-farm/reports/purchases        → reports.purchases
/fish-farm/reports/feed             → reports.feed
/fish-farm/reports/fish-stock       → reports.fish-stock
/fish-farm/reports/ponds            → reports.ponds
/fish-farm/reports/fcr-growth       → reports.fcr-growth
/fish-farm/reports/income           → reports.income
/fish-farm/reports/expenses         → reports.expenses
/fish-farm/reports/profit-loss      → reports.profit-loss

## Authentication (Phase 1 — implemented)

| Method | URI        | Route name     | Middleware |
| ------ | ---------- | -------------- | ---------- |
| GET    | `/login`   | `login`        | `guest`    |
| POST   | `/login`   | `login.store`  | `guest`    |
| POST   | `/logout`  | `logout`       | `auth`     |

## Settings (Phase 1 — implemented, permission-enforced)

| Method | URI                              | Route name                  | Permission                       |
| ------ | -------------------------------- | --------------------------- | -------------------------------- |
| GET    | `/settings/company`              | `settings.company.edit`     | `company.view`                   |
| PUT    | `/settings/company`              | `settings.company.update`   | `company.update`                 |
| GET    | `/settings/profile`              | `settings.profile.edit`     | `auth` only (own account)        |
| PUT    | `/settings/profile`              | `settings.profile.update`   | `auth` only (own account)        |
| GET    | `/settings/users`                | `settings.users.index`      | `users.view`                     |
| GET    | `/settings/users/create`         | `settings.users.create`     | `users.create`                   |
| POST   | `/settings/users`                | `settings.users.store`      | `users.create`                   |
| GET    | `/settings/users/{user}/edit`    | `settings.users.edit`       | `users.update`                   |
| PUT    | `/settings/users/{user}`         | `settings.users.update`     | `users.update`                   |
| PATCH  | `/settings/users/{user}/toggle-active` | `settings.users.toggle-active` | `users.update`          |
| DELETE | `/settings/users/{user}`         | `settings.users.destroy`    | `users.delete`                   |
| GET    | `/settings/roles`                | `settings.roles.index`      | `roles.view`                     |
| GET    | `/settings/roles/create`         | `settings.roles.create`     | `roles.create`                   |
| POST   | `/settings/roles`                | `settings.roles.store`      | `roles.create`                   |
| GET    | `/settings/roles/{role}/edit`    | `settings.roles.edit`       | `roles.update` + `permissions.manage` |
| PUT    | `/settings/roles/{role}`         | `settings.roles.update`     | `roles.update` + `permissions.manage` |
| DELETE | `/settings/roles/{role}`         | `settings.roles.destroy`    | `roles.delete`                   |
| GET    | `/settings/permissions`          | `settings.permissions.index`| `permissions.view`               |

> The old `/settings/farm` and `/settings/system` placeholders were replaced by
> `/settings/company` and `/settings/permissions` respectively. The business
> identity is the **company** — there is no separate “farm management” page.

All `/fish-farm/*` and `/settings/*` routes are inside a
`Route::middleware(['auth'])->group(...)`, and every business route additionally
carries a `permission:` middleware.

## 3. Verified route count
`php artisan route:list --except-vendor` currently reports **211 routes**.

Every module (dashboard, ponds, pond ledger, feed, FCR & growth, fish stock,
sales, customers, suppliers, parties, finance, reports, settings) is implemented
and returns an Inertia/React page. Each `/fish-farm/*` route is `auth` +
`permission:*` gated, and the controller re-asserts the policy per record.

There is no placeholder/pending controller: a route either renders real data or
an honest empty state — never a fabricated figure.

## 4. Route names for write operations
Modules follow Laravel resource conventions so the shape stays predictable.
**Pond Management (Phase 2) is implemented**, with these routes — each also
carrying `permission:` middleware:

| Action | Method      | URI                                | Route name             | Permission           |
| ------ | ----------- | ---------------------------------- | ---------------------- | -------------------- |
| list   | GET         | `/fish-farm/ponds`                 | `ponds.index`          | `pond.view`          |
| create | GET         | `/fish-farm/ponds/create`          | `ponds.create`         | `pond.create`        |
| store  | POST        | `/fish-farm/ponds`                 | `ponds.store`          | `pond.create`        |
| show   | GET         | `/fish-farm/ponds/{pond}`          | `ponds.show`           | `pond.view`          |
| edit   | GET         | `/fish-farm/ponds/{pond}/edit`     | `ponds.edit`           | `pond.update`        |
| update | PUT/PATCH   | `/fish-farm/ponds/{pond}`          | `ponds.update`         | `pond.update`        |
| delete | DELETE      | `/fish-farm/ponds/{pond}`          | `ponds.destroy`        | `pond.delete`        |
| status | GET         | `/fish-farm/ponds/status`          | `ponds.status`         | `pond.view`          |
| types  | GET         | `/fish-farm/ponds/types`           | `ponds.types.index`    | `pond_type.view`     |
| types create | GET   | `/fish-farm/ponds/types/create`    | `ponds.types.create`   | `pond_type.create`   |
| types store  | POST  | `/fish-farm/ponds/types`           | `ponds.types.store`    | `pond_type.create`   |
| types edit   | GET   | `/fish-farm/ponds/types/{pondType}/edit` | `ponds.types.edit` | `pond_type.update` |
| types update | PUT   | `/fish-farm/ponds/types/{pondType}`| `ponds.types.update`   | `pond_type.update`   |
| types delete | DELETE| `/fish-farm/ponds/types/{pondType}`| `ponds.types.destroy`  | `pond_type.delete`   |

> **Ordering rule:** the literal segments `types` and `status` are declared
> **before** the `/{pond}` wildcard, so they are never captured by route model
> binding. The type wildcard is `{pondType}`, so a FormRequest must read
> `$this->route('pondType')` (camelCase), **not** `pond_type`.

Plus module-specific actions (e.g. `feed.usages.store`, `sales.payment.store`).

## 5. Backend mutations from the UI

Internal REST-style routes are acceptable for in-page interactions (tables,
modals) when a full redirect would be disruptive. Rules:

- Non-GET routes always require CSRF (`@csrf`).
- Destructive actions use `@method('DELETE')`.
- Every mutation goes through a FormRequest.
- Every mutation is authorized (policy or `permission` middleware).
- Responses are either a redirect with a flash message, or JSON — never both.

## 6. Adding a route

1. Add it to the correct module group in `routes/web.php`.
2. Give it a name following the module prefix.
3. Add the corresponding entry to `config/navigation.php` if it should appear in
   the sidebar (use the route **name**).
4. Enforce authorization when the module is implemented.
5. Update this document and `docs/CHANGELOG.md`.