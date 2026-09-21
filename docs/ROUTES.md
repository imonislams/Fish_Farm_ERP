# Routes — Fish Farm ERP

Read `docs/PROJECT.md` and `docs/ARCHITECTURE.md` first.

Route files: `routes/web.php` (application), `routes/auth.php` (authentication),
`routes/console.php` (console commands). Both web files are registered in
`bootstrap/app.php`.

---

## 1. Rules

1. **Every route has a name.** Blade uses `route('name')`, never a raw URL.
2. Names follow the module prefix: `ponds.index`, `ponds.create`, `fcr.growth`.
3. Route groups use `Route::prefix(...)->name('module.')`.
4. Module pages not implemented yet resolve to `Settings\PendingController`,
   which renders an honest "not implemented" page. **Nothing fakes data.**
5. Authorization middleware attaches per group as modules are implemented.
6. `route('login')` / `route('logout')` are referenced defensively with
   `Route::has()` so the UI works before auth routes exist.

## 2. Layout

```
/                                   → redirect to /dashboard
/dashboard                          → dashboard
/offline                            → offline (PWA fallback reference)

/fish-farm/ponds                    → ponds.index
/fish-farm/ponds/create             → ponds.create
/fish-farm/ponds/types              → ponds.types.index
/fish-farm/ponds/status             → ponds.status

/fish-farm/pond-ledger              → ledger.index
/fish-farm/pond-ledger/transactions → ledger.transactions

/fish-farm/feed                     → feed.index
/fish-farm/feed/types               → feed.types.index
/fish-farm/feed/stock               → feed.stock
/fish-farm/feed/purchases           → feed.purchases.index
/fish-farm/feed/usages              → feed.usages.index

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
/fish-farm/fish/stockings           → fish.stockings.index
/fish-farm/fish/mortalities         → fish.mortalities.index
/fish-farm/fish/harvests            → fish.harvests.index

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
`php artisan route:list --except-vendor` currently reports **74 routes**.

**Phase 1 (implemented & enforced):** authentication, dashboard, and all 18
settings routes.

**Phase 2+ (not implemented yet):** the `/fish-farm/*` module routes resolve to
`PendingController`, which renders an honest “not implemented” page and invents
no data. They are already permission-gated, so they become reachable the moment
their module is built and the role is granted the permission.

## 4. Conventional route names for write operations

When a module is implemented, follow Laravel resource conventions so the shape
stays predictable:

| Action | Method      | URI                      | Route name            |
| ------ | ----------- | ------------------------ | --------------------- |
| list   | GET         | `/fish-farm/ponds`       | `ponds.index`         |
| create | GET         | `/fish-farm/ponds/create`| `ponds.create`        |
| store  | POST        | `/fish-farm/ponds`       | `ponds.store`         |
| show   | GET         | `/fish-farm/ponds/{pond}`| `ponds.show`          |
| edit   | GET         | `/fish-farm/ponds/{pond}/edit` | `ponds.edit`    |
| update | PUT/PATCH   | `/fish-farm/ponds/{pond}`| `ponds.update`        |
| delete | DELETE      | `/fish-farm/ponds/{pond}`| `ponds.destroy`       |

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