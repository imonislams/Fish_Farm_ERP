# Changelog — Fish Farm ERP

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