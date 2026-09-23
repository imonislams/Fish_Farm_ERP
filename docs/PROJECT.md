# Fish Farm ERP — Project Overview

> **IMPORTANT: Any developer or AI agent must read this document and the relevant
> documentation files before modifying the project.**

---

## 1. Project name

**Fish Farm ERP** — a server-rendered Laravel application for managing fish farm
operations end to end.

## 2. Purpose

Manage the complete operating cycle of a fish farm in one system:

Farms, ponds, fish species, stocking, mortality, harvest, feed purchasing, feed
stock, feed usage, growth, FCR, pond inspections and schedules, customers, sales,
customer payments and dues, suppliers, purchases, supplier payments and dues,
general parties, pond ledger, income, expenses, reports, dashboard, users, roles,
permissions, settings, notifications, and PWA support.

**The system must use real database data.** No module may display invented
figures. Where a module is not implemented, the UI must say so plainly.

## 3. Technology stack

| Layer          | Choice                                              |
| -------------- | --------------------------------------------------- |
| Framework      | Laravel 12 (PHP 8.2)                                 |
| Database       | MySQL / MariaDB                                      |
| Templating     | Blade (server-rendered)                              |
| Frontend       | HTML, CSS, vanilla JavaScript                        |
| Build tool     | Vite 7 + Tailwind CSS 4                              |
| Auth           | Laravel built-in authentication                     |
| Authorization  | Laravel policies / gates / permission middleware     |
| Routing        | REST-style internal routes                           |
| PWA            | Manifest + service worker + offline fallback         |

### Explicitly NOT used

Do **not** introduce: React, Vue, Inertia, Livewire, Bootstrap, or any other
frontend framework or CSS framework. The UI is Blade plus vanilla JavaScript.
Adding a dependency requires an explicit decision recorded in CHANGELOG.md.

## 4. Main modules

1. Dashboard
2. Pond Management
3. Pond Ledger
4. Food (Feed) Management
5. FCR & Growth
6. Fish Stock
7. Sales
8. Customers
9. Suppliers
10. Party
11. Finance
12. Reports
13. Notifications
14. Settings
15. Users / Roles / Permissions
16. PWA

See `docs/MODULES.md` for per-module detail and current implementation status.

## 5. Architecture summary

Modular, domain-oriented Laravel:

```
app/
├── Http/
│   ├── Controllers/<Domain>/    one folder per business domain
│   ├── Requests/                one FormRequest per write operation
│   └── Middleware/
├── Models/                      relationships, casts, model-level behaviour
├── Services/<Domain>/           ALL business logic and calculations
├── Policies/                    authorization
├── Support/                     small framework-level value helpers
└── Providers/
```

Controllers stay thin. Business logic lives in services. See
`docs/ARCHITECTURE.md` for the full responsibility matrix.

## 6. UI philosophy

- Dark green sidebar, green/teal branding, white content area.
- Green/teal gradient hero sections.
- Modern KPI cards, soft shadows, rounded corners, clean tables.
- Consistent colour and spacing tokens everywhere — no per-page colour choices.
- Bengali-friendly Unicode support.
- Responsive across desktop, laptop, tablet and mobile.
- Action buttons must never be clipped on small screens.

See `docs/UI_GUIDELINES.md`.

## 7. Database philosophy

- Real tables, real relationships, real foreign keys.
- Incremental migrations only. **Never** `migrate:fresh` / `migrate:refresh` /
  `db:wipe` unless the user explicitly requests it.
- **Single company (Version 1).** There is one `companies` row and only `users`
  reference it. Business tables carry **no** `farm_id`/`company_id` column — the
  company context is singular and implicit. No tenant middleware, no tenant
  switching, no company selector. See `docs/DATABASE.md` §1.
- No duplicate tables: inspect the existing schema before adding anything.
- Indexes on columns used for filtering, joining and reporting.
- Multi-record changes run inside `DB::transaction()`.

See `docs/DATABASE.md`.

## 8. Authentication
**Implemented (Phase 1).** Laravel's built-in session guard — no external auth
package (no Breeze / Jetstream / Fortify).

- `GET /login` — form (guest only) · `POST /login` — authenticate ·
  `POST /logout` — destroy session.
- Controller: `App\Http\Controllers\Auth\LoginController` (thin); validation in
  `App\Http\Requests\Auth\LoginRequest`.
- Security: CSRF on both POST routes, rate limiting (5 attempts/min per
  email+IP), session id regenerated on login (fixation protection), generic
  failure message, and **inactive accounts are refused even with valid
  credentials**.
- Protected routes redirect guests to `/login`; after login the user returns to
  the page they originally requested (`intended()`).
- `routes/auth.php` holds the routes; `bootstrap/app.php` loads it and sets
  `redirectGuestsTo(route('login'))`.

### Initial administrator
Created by `AdminUserSeeder`, which reads `ADMIN_EMAIL` / `ADMIN_PASSWORD` from
`.env`. **No password is hard-coded**: if `ADMIN_PASSWORD` is empty a random one
is generated and printed once by the seeder. See §14.

## 9. Authorization
Role-based with granular permissions (`pond.view`, `users.create`, …). Enforced
**server-side** at route level via the `permission:` middleware alias
(`App\Http\Middleware\EnsurePermission`), plus policies for per-record checks —
**never** by merely hiding a menu item. A user cannot reach a protected page by
editing a URL, and cannot escalate their own role through a form.

Blade directives `@permission(...)` / `@role(...)` control **display only**.
A Super Admin bypass lives solely in `Gate::before()`. See
`docs/PERMISSIONS.md`.

## 10. PWA

Installable app shell: manifest, icons, service worker, offline fallback page and
network-status awareness. Static assets are cached; **authenticated responses and
all non-GET requests are never cached**. The ERP needs the server for data and the
UI must never imply a write succeeded while offline. See `docs/PWA.md`.

## 11. Development rules

1. **Inspect before changing.** Read the docs, then the code, then plan the
   smallest correct change.
2. Reuse existing components, services and tables. Do not duplicate.
3. Preserve working functionality. Do not rebuild what works.
4. Do not break existing modules.
5. Keep the UI consistent with the design system.
6. Keep authorization enforced (route middleware + policy). Version 1 is
   single-company — do not add tenant scoping.
7. Keep database calculations accurate and centralized in services.
8. Update the documentation when implementation changes.
9. Do not create or run tests unless the user explicitly asks.
10. Do not commit or push to Git — the user controls Git operations.

## 12. Important restrictions

- No destructive database commands without explicit user request.
- No fake business data, ever. Unimplemented modules render an honest
  "not implemented" state.
- No new frontend frameworks or CSS frameworks.
- No new dependencies without a recorded justification.
- No business calculations inside Blade templates.
- No business calculations duplicated across controllers.

## 13. Documentation rules

- `docs/` is the permanent source of architectural knowledge and is part of the
  deliverable.
- Documentation must be updated in the same change as the code it describes.
- If the docs and the code disagree, **inspect the code** and update the docs
  only after understanding why they differ. Never trust stale docs over the
  implementation.
- Record meaningful changes in `docs/CHANGELOG.md`.

## 14. Initial setup (first run)

```powershell
# 1. Start MySQL/MariaDB in XAMPP, then create the database
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE fish_farm_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Set the first administrator in .env (optional; a random one is generated)
#    ADMIN_EMAIL=you@example.com
#    ADMIN_PASSWORD=...

# 3. Incremental migrations, then seed system data
& "C:\xampp\php\php.exe" artisan migrate
& "C:\xampp\php\php.exe" artisan db:seed
# 4. Build front-end assets
npm run build
```

Seeding creates **only**: the permission catalogue, the 7 system roles, the one
company row and the first Super Admin. **No business data is ever seeded.**

The seeders are idempotent — re-running updates existing rows rather than
duplicating them, and the company record is left untouched if it already exists.

## 15. Current status
**Phase 1 complete**: authentication, single company, multiple users, roles and
permissions, company settings, user management, role management, permission
reference and the user profile are implemented and permission-enforced.

**Phase 2 complete — Pond Management**: real `pond_types` and `ponds` tables,
models, services (`Pond\PondService`, `Pond\PondTypeService`), FormRequests,
policies, full CRUD, server-side search/filter/pagination, a status overview page
and an honest pond details page. Canonical pond statuses and units live in
`config/ponds.php`.

**Phase 3 complete — Fish Stock**: real `fish_species`, `fish_stockings`,
`fish_mortalities` and `harvests` tables, models with relationships, the
`Fish\FishStockService` (the single definition of live stock = stocked −
mortality − harvested, the non-negative guard, derived weights and all writes)
and `Fish\FishSpeciesService`, FormRequests, policies, species CRUD and the
stocking/mortality/harvest recording pages plus a stock dashboard. Stock is never
typed in directly — it is always derived from records, and can never go negative.
All routes are permission-gated (`pond.*`, `pond_type.*`, `fish.*`) **and**
policy-checked. Canonical values live in `config/ponds.php` and `config/fish.php`.

**Phase 4 complete — Feed (Food) Management**: real `feed_types`,
`feed_purchases`, `feed_usages` and `feed_stock_adjustments` tables, models with
relationships, the `Feed\FeedStockService` (the single definition of feed stock =
purchases + adjustments-in − usage − adjustments-out, the non-negative guard, the
derived line cost, the low-stock signal and all writes) and `Feed\FeedTypeService`,
FormRequests, policies, feed type CRUD, purchase/usage/adjustment recording pages
and a stock dashboard. Feed stock is never typed in directly, can never go
negative, and every manual adjustment records a reason.

**Phase 5 complete — Pond Ledger**: a real `pond_ledger_entries` table, the
`Pond\PondLedgerService` (the single write path `record()`, reversal
`reverseSource()`, per-pond profit and category totals — delegating the sign
convention to `Finance\LedgerRules`), a request, a policy, a ledger dashboard and
a filterable transactions list. `Pond Profit = income − expense`; a loss is shown
as a loss, never clamped. The pond details page now shows real pond financials.
Because Sales/Finance do not exist yet, entries can be recorded by hand
(`source_type = manual`) — the modules will later write through the same service.

Every *other* business module (FCR, sales, customers, suppliers, party, finance,
reports, notifications) is still **scaffolded only** and renders an honest
"not implemented" state — no fake data.

For what exists versus what is pending, see:
- `docs/MODULES.md` — module-by-module status
- `docs/CHANGELOG.md` — what was added and when