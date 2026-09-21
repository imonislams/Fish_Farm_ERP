# Modules — Fish Farm ERP

Read `docs/PROJECT.md` and `docs/ARCHITECTURE.md` first.

---

## Status legend

| Symbol | Meaning                                                                 |
| ------ | ----------------------------------------------------------------------- |
| ✅     | Implemented with real database data                                     |
| 🟡     | Partially implemented (some real pages/services, rest pending)          |
| ⚪     | **Scaffolded only** — route + nav + view placeholder; no tables, no data |

**Phase 1 added a 17th area — the Administration foundation — which is fully
implemented (✅).** Every *business* module remains ⚪ (scaffolded only). The
Dashboard is 🟡: it is implemented and permission-gated, but honestly reports its
KPIs as unavailable until their modules exist.

Module flags in `config/fishfarm.php` (`modules.*`) are all `false` except
`dashboard`. Flip a flag to `true` when the module is genuinely implemented.

### Phase 1 additions
| Module                    | Status | Routes / Pages                                    |
| ------------------------- | ------ | ------------------------------------------------- |
| Authentication            | ✅     | `/login`, `/logout`                               |
| Company Settings          | ✅     | `/settings/company`                               |
| User Management           | ✅     | `/settings/users` (+ create/edit/toggle/delete)   |
| Role Management           | ✅     | `/settings/roles` (+ create/edit/delete)          |
| Permission Reference      | ✅     | `/settings/permissions`                           |
| User Profile              | ✅     | `/settings/profile`                               |
| Administration (§17)      | ✅     | see below                                         |

---

## 1. Dashboard — 🟡

- **Purpose:** live farm overview — KPIs, analytics, recent activity, quick actions.
- **Routes:** `dashboard`
- **Controllers:** `Http/Controllers/Dashboard/DashboardController`
- **Models:** none yet
- **Services:** `Services/Dashboard/DashboardMetricsService`
- **Tables:** none directly; aggregates other modules' tables
- **Business rules:** must show **real** database values only. Until a module
  exists, its metric returns `Metric::pending()` and renders `—`.
- **Permissions:** `dashboard.view` (implicit for any authenticated user)
- **UI:** `resources/views/dashboard/index.blade.php`
- **Calculations:** all aggregation lives in `DashboardMetricsService`.

Required KPIs (all currently pending): Today's Sales, Today's Feed, Today's
Income, Today's Collection, Total Due, Cash Position, Average FCR, Today's
Inspection, Inspection Due, Best Pond.

Analytics blocks: Pond Status, Fish Stock, Feed Usage, Growth, Sales,
Income vs Expense. Recent: Transactions, Inspections.
Quick actions: Fish Sale, Feed Entry, Fish Stock, Customer Collection,
New Inspection.

**To complete:** implement each module's tables, then replace the
`Metric::pending(...)` calls with real aggregate queries (`DashboardMetricsService`).

## 2. Pond Management — ⚪

- **Purpose:** ponds and their types/status.
- **Routes:** `ponds.index`, `ponds.create`, `ponds.types.index`, `ponds.status`
- **Tables:** `ponds`, `pond_types` (planned)
- **Permissions:** `pond.view`, `pond.create`, `pond.update`, `pond.delete`,
  `pond.type.manage`
- **UI:** `resources/views/ponds/`

## 3. Pond Ledger — ⚪

- **Purpose:** money in/out per pond → per-pond profitability.
- **Routes:** `ledger.index`, `ledger.transactions`
- **Tables:** `pond_ledger_entries` (planned)
- **Services:** `Services/Pond/PondLedgerService` (planned)
- **Business rules:** entries written only by the owning service; each records
  `source_type` + `source_id`; deleting a source reverses the entry in the same
  transaction. See `docs/BUSINESS_LOGIC.md` §4.
- **Permissions:** `ledger.view`, `finance.view`

## 4. Food (Feed) Management — ⚪

- **Purpose:** feed catalogue, stock, purchasing and usage.
- **Routes:** `feed.index`, `feed.types.index`, `feed.stock`,
  `feed.purchases.index`, `feed.usages.index`
- **Tables:** `feed_types`, `feed_purchases`, `feed_usages`,
  `feed_stock_adjustments` (planned)
- **Services:** `Services/Feed/FeedStockService` ✅ (rules implemented)
- **Business rules:** purchase → stock IN; usage → stock OUT; adjustment →
  IN/OUT with a reason. Stock never negative. Never changed silently.
- **Permissions:** `feed.view`, `feed.purchase`, `feed.usage`, `feed.adjust`,
  `feed.type.manage`
- **UI:** `resources/views/feed/`

## 5. FCR & Growth — ⚪ (service ✅)

- **Purpose:** feed conversion ratio, growth tracking, inspections.
- **Routes:** `fcr.index`, `fcr.inspections.index`, `fcr.inspections.create`,
  `fcr.growth`, `fcr.feed-growth`, `fcr.comparison`, `fcr.schedules.index`,
  `fcr.reports`
- **Tables:** `growth_records`, `inspections`, `inspection_schedules`,
  `feed_usages`, `fish_stockings` (planned)
- **Services:** `Services/Fcr/FcrCalculator` ✅, `Services/Fcr/FcrResult` ✅
- **Business rules:** `FCR = feed consumed / weight gain`. Five explicit states;
  never divides by zero; unavailable FCR renders `—`, never `0`. See
  `docs/BUSINESS_LOGIC.md` §1.
- **Permissions:** `fcr.view`, `fcr.inspection.create`, `fcr.inspection.update`,
  `fcr.schedule.manage`, `growth.view`, `growth.create`
- **UI:** `resources/views/fcr/`

## 6. Fish Stock — ⚪ (service ✅)

- **Purpose:** species, stocking, mortality, harvest.
- **Routes:** `fish.index`, `fish.species.index`, `fish.stockings.index`,
  `fish.mortalities.index`, `fish.harvests.index`
- **Tables:** `fish_species`, `fish_stockings`, `fish_mortalities`, `harvests`
  (planned)
- **Services:** `Services/Fish/FishStockService` ✅ (rules implemented)
- **Business rules:** stocking → IN; mortality/harvest → OUT; stock never
  negative; every movement records its source.
- **Permissions:** `fish.view`, `fish.stock`, `fish.mortality`, `fish.harvest`,
  `fish.species.manage`
- **UI:** `resources/views/fish/`

## 7. Sales — ⚪

- **Purpose:** fish sales to customers, sale items, sales ledger.
- **Routes:** `sales.index`, `sales.list`, `sales.ledger`
- **Tables:** `sales`, `sale_items` (planned)
- **Services:** `Services/Sales/SalesService` (planned)
- **Business rules:** creating a sale writes sale + items + stock movement +
  customer due + pond ledger entry **in one transaction**.
- **Permissions:** `sales.view`, `sales.create`, `sales.update`, `sales.delete`
- **UI:** `resources/views/sales/`

## 8. Customers — ⚪

- **Purpose:** customers, payments, dues.
- **Routes:** `customers.index`, `customers.payments.index`, `customers.dues`
- **Tables:** `customers`, `customer_payments` (planned)
- **Services:** `Services/Finance/CustomerBalanceService` (planned)
- **Business rules:** `Due = Sales − Payments`, using `LedgerRules` sign
  convention.
- **Permissions:** `customer.view`, `customer.create`, `customer.update`,
  `customer.delete`, `customer.payment.create`
- **UI:** `resources/views/customers/`

## 9. Suppliers — ⚪

- **Purpose:** suppliers, purchases, payments, dues.
- **Routes:** `suppliers.index`, `suppliers.create`, `suppliers.purchases.index`,
  `suppliers.payments.index`, `suppliers.dues`
- **Tables:** `suppliers`, `purchases`, `purchase_items`, `supplier_payments`
  (planned)
- **Services:** `Services/Finance/SupplierBalanceService` (planned)
- **Business rules:** `Due = Purchases − Payments`.
- **Permissions:** `supplier.view`, `supplier.create`, `supplier.update`,
  `supplier.delete`, `supplier.purchase.create`, `supplier.payment.create`
- **UI:** `resources/views/suppliers/`

## 10. Party — ⚪

- **Purpose:** generic counterparties and their ledger.
- **Routes:** `parties.index`, `parties.create`, `parties.transactions`,
  `parties.ledger`
- **Tables:** `parties`, `party_transactions` (planned)
- **Business rules:** `Balance = Debits − Credits` (positive = they owe the farm).
- **Permissions:** `party.view`, `party.create`, `party.update`, `party.delete`,
  `party.transaction.create`
- **UI:** `resources/views/parties/`

## 11. Finance — ⚪

- **Purpose:** income, expenses and categories.
- **Routes:** `finance.income.index`, `finance.expenses.index`
- **Tables:** `income_entries`, `expense_categories`, `expense_entries` (planned)
- **Services:** `Services/Finance/LedgerRules` ✅ (rules implemented)
- **Business rules:** `Net Profit = Income − Expense`; losses are displayed as
  losses, never clamped to zero; percentages guard against a zero base.
- **Permissions:** `finance.view`, `income.*`, `expense.*`,
  `expense.category.manage`, `ledger.view`
- **UI:** `resources/views/finance/`

## 12. Reports — ⚪

- **Purpose:** the nine reports, all with date range, search, filters,
  pagination, summary, table, print-ready layout and export-ready architecture.
- **Routes:** `reports.sales`, `reports.purchases`, `reports.feed`,
  `reports.fish-stock`, `reports.ponds`, `reports.fcr-growth`,
  `reports.income`, `reports.expenses`, `reports.profit-loss`
- **Tables:** reads across all modules — owns none
- **Services:** `Services/Reports/ReportService` (planned) — **one** reusable
  reporting architecture; report calculation logic is never duplicated.
- **Permissions:** `reports.view`, `reports.export`, `reports.financial`
- **UI:** `resources/views/reports/`

## 13. Notifications — ⚪

- **Purpose:** database-driven alerts.
- **Tables:** `notifications` (planned)
- **Types:** low feed stock, inspection due, inspection overdue, customer due,
  supplier due, payment reminders, important system events.
- **Business rules:** generated by services/scheduled commands, never by views.
- **Permissions:** `notifications.manage`
- **UI:** header bell (present) + a notifications page (planned)

## 14. Settings / Company — ✅
- **Purpose:** edit the single company's business identity and branding.
- **Routes:** `settings.company.edit`, `settings.company.update`
- **Controllers:** `Http/Controllers/Settings/CompanyController`
- **Services:** `Services/Settings/CompanyService` (transactional write + logo file handling)
- **Requests:** `Http/Requests/Settings/UpdateCompanyRequest`
- **Tables:** `companies` (implemented)
- **Business rules:** exactly one row; never hard-deleted (`status` → inactive).
  Logo stored on the `public` disk under `company/`; replacing or removing it
  deletes the previous file. Cache invalidated on write (`CompanyContext::forget()`).
- **Permissions:** `company.view`, `company.update`
- **UI:** `resources/views/settings/company/edit.blade.php`
- **Notes:** there is **no** separate “Farm Management” page — the company *is*
  the business identity. The name/logo appear dynamically in the sidebar, auth
  layout and footer via `App\View\Composers\CompanyComposer` + `App\Support\CompanyContext`.
  A future `settings` key/group/value table is still planned for system-level options.

## 15. Users / Roles / Permissions — ✅
- **Purpose:** user administration, role definition and permission reference.
- **Routes:** `settings.users.*`, `settings.roles.*`, `settings.permissions.index`
- **Controllers:** `Settings/UserController`, `Settings/RoleController`,
  `Settings/PermissionController`, `Settings/ProfileController`
- **Services:** `Settings/UserService`, `Settings/RoleService`
- **Requests:** `Settings/{Store,Update}UserRequest`, `Settings/{Store,Update}RoleRequest`,
  `Settings/UpdateProfileRequest`
- **Tables:** `users`, `roles`, `permissions`, `role_user`, `permission_role` ✅
- **Business rules:**
  - Version 1: a user holds **one** primary role (`sync()`).
  - Every user belongs to the single company; `company_id` is never taken from input.
  - Users cannot change their own role or deactivate themselves.
  - The last active Super Admin cannot be deleted.
  - System roles cannot be deleted; a role assigned to users cannot be deleted.
  - `role_id` validated with `Rule::exists`; `permissions[]` validated against
    existing permission names.
- **Permissions:** `users.*`, `roles.*`, `permissions.view` / `permissions.manage`
- **UI:** `resources/views/settings/{users,roles,permissions,profile}/`
- **Detail:** `docs/PERMISSIONS.md`

## 17. Administration & Authentication — ✅ (Phase 1)
- **Purpose:** sign-in and the access-control foundation every other module relies on.
- **Routes:** `login`, `login.store`, `logout`
- **Controllers:** `Auth/LoginController`; **Request:** `Auth/LoginRequest`
- **Tables:** `users` (with `company_id`, `is_active`), `companies`, `roles`,
  `permissions`, pivots
- **Business rules:** Laravel built-in session guard; 5 attempts/min per
  email+IP; session regenerated on login; **inactive users are refused even with
  valid credentials**; deactivated users are logged out mid-session by
  `EnsurePermission`.
- **Permissions:** `dashboard.view` on the dashboard; everything else per page
- **UI:** `resources/views/auth/login.blade.php`
- **Initial admin:** `AdminUserSeeder` from `.env` — never a shipped default
  password (`docs/PERMISSIONS.md` §7a).

## 16. PWA — ✅ (foundation)

- **Purpose:** installable app shell, cached static assets, offline fallback,
  network awareness, safe updates.
- **Files:** `public/manifest.webmanifest`, `public/sw.js`,
  `public/offline.html`, `public/icons/**`, `resources/js/pwa.js`,
  `resources/js/network.js`
- **Business rules:** never cache authenticated responses or non-GET requests;
  never imply a write succeeded while offline.
- **Detail:** `docs/PWA.md`

---

## Implementing a module

Follow the 12-step checklist in `docs/ARCHITECTURE.md` §10. Then update the
status symbol for that module here, flip its flag in `config/fishfarm.php`, and
record it in `docs/CHANGELOG.md`.