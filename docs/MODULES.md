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
implemented (✅).** The Dashboard is 🟡: it is implemented and permission-gated,
but honestly reports its KPIs as unavailable until their modules exist.

**Phase 2** implemented Pond Management (✅); **Phase 3** Fish Stock (✅);
**Phase 4** Feed (Food) Management (✅); **Phase 5** the Pond Ledger (✅). All have
real tables. Every other business module remains ⚪ (scaffolded only).

Module flags in `config/fishfarm.php` (`modules.*`) are `true` only for
`dashboard`, `ponds`, `fish`, `feed` and `ledger`. Flip a flag to `true` when the
module is genuinely implemented.

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

### Phase 2 additions
| Module                    | Status | Routes / Pages                                    |
| ------------------------- | ------ | ------------------------------------------------- |
| Pond Management (§2)      | ✅     | `/fish-farm/ponds` (+ CRUD, types, status)        |

### Phase 3 additions
| Module                    | Status | Routes / Pages                                    |
| ------------------------- | ------ | ------------------------------------------------- |
| Fish Stock (§6)           | ✅     | `/fish-farm/fish` (+ species, stockings, mortality, harvests) |

### Phase 4 additions
| Module                    | Status | Routes / Pages                                    |
| ------------------------- | ------ | ------------------------------------------------- |
| Food/Feed Management (§4) | ✅     | `/fish-farm/feed` (+ types, purchases, usages, adjustments) |

### Phase 5 additions
| Module                    | Status | Routes / Pages                                    |
| ------------------------- | ------ | ------------------------------------------------- |
| Pond Ledger (§3)          | ✅     | `/fish-farm/pond-ledger` (+ transactions, entry)   |

---

## 1. Dashboard — ✅

- **Purpose:** live farm overview — date-filtered KPIs, charts, low-feed signal, recent activity.
- **Routes:** `dashboard` (accepts `?range=today|this_week|this_month|last_month|this_year|custom`, plus `from`/`to`)
- **Controllers:** `Http/Controllers/Dashboard/DashboardController`
- **Models:** none directly; aggregates every module's tables
- **Services:** `Services/Dashboard/DashboardService`
- **Tables:** none directly; reads across every module
- **Business rules:** every figure is a REAL aggregate over the chosen range — nothing is
  invented. A metric with no data is a genuine 0 / empty list.
- **Permissions:** `dashboard.view` (implicit for any authenticated user)
- **UI (React):** `resources/js/react/Pages/Dashboard.jsx`
- **Calculations:** all aggregation lives in `DashboardService` (KPIs, sales/expense trends,
  stock & feed breakdowns, low-feed, recent activity, calendar events).

KPIs: Sales, Income, Expense, Profit/Loss, Purchases, Customer Due, Supplier Due, Feed Used
(date-scoped) + Ponds, Live Fish, Feed Stock, Customers (live snapshot).
Charts: sales trend, expense trend, fish-stock breakdown, feed breakdown — all per range.
Recent activity: real records merged across sales/purchases/income/expenses/stocking/
mortality/harvest/feed/inspection/growth, newest first.

## 2. Pond Management — ✅ (Phase 2)

- **Purpose:** ponds and their types/status.
- **Routes:** `ponds.index`, `ponds.create`, `ponds.store`, `ponds.show`,
  `ponds.edit`, `ponds.update`, `ponds.destroy`, `ponds.status`,
  `ponds.types.index`, `ponds.types.create`, `ponds.types.store`,
  `ponds.types.edit`, `ponds.types.update`, `ponds.types.destroy`
- **Controllers:** `Pond/PondController`, `Pond/PondTypeController`,
  `Pond/PondStatusController`
- **Services:** `Pond/PondService` (filtered/paginated query, status → `is_active`
  derivation, real status counts), `Pond/PondTypeService` (write-path delete
  guard)
- **Requests:** `Pond/{Store,Update}PondRequest`, `Pond/{Store,Update}PondTypeRequest`
- **Policies:** `PondPolicy`, `PondTypePolicy`
- **Tables:** `ponds`, `pond_types` ✅
- **Business rules:**
  - `pond_number` is unique; `pond_types.name` is unique.
  - A pond must be classified — `pond_type_id` is required and FK-checked.
  - A pond type **currently used by any pond cannot be deleted** (FK
    `restrictOnDelete` + `PondTypeService` guard). Reassign or mark inactive.
  - `is_active` is **derived from `status`** (`active`/`empty` = usable), never
    accepted from the form, so the boolean and the status cannot contradict.
  - Size is required and `> 0`; depth is optional; decimal values, never float.
- **Permissions:** `pond.view`, `pond.create`, `pond.update`, `pond.delete`,
  `pond_type.view`, `pond_type.create`, `pond_type.update`, `pond_type.delete`
  (the Phase 1 `pond.type.manage` key is retired — see `docs/PERMISSIONS.md` §5)
- **UI:** `resources/views/ponds/` (`index`, `create`, `edit`, `show`, `_form`,
  `status`, `types/*`)
- **Notes:** the pond details page shows only real database values; sections for
  not-yet-built modules (fish stock, feed, growth, inspections, mortality,
  harvest, finance) render honest "no records yet" empty states.

## 3. Pond Ledger — ✅ (Phase 5)

- **Purpose:** money in/out per pond → per-pond profitability.
- **Routes:** `ledger.index`, `ledger.transactions`,
  `ledger.transactions.create`, `ledger.transactions.store`,
  `ledger.transactions.destroy`
- **Controllers:** `Pond/PondLedgerController` (dashboard),
  `Pond/PondLedgerEntryController` (transactions, create, store, destroy)
- **Services:** `Pond/PondLedgerService` — the ONE place ledger entries are
  written (`record()`), reversed (`reverseSource()`) and totalled (per-pond
  summary, farm-wide totals, category breakdowns). Delegates the sign convention
  to `Finance/LedgerRules`.
- **Requests:** `Pond/StorePondLedgerEntryRequest`
- **Policies:** `PondLedgerEntryPolicy`
- **Tables:** `pond_ledger_entries` ✅
- **Business rules:** (docs/BUSINESS_LOGIC.md §4)
  - `Pond Profit = Σ credits (income) − Σ debits (expenses)`. **A negative result
    is a real loss and is displayed as one** — never clamped to zero.
  - Entries are written **only** through `PondLedgerService`; a controller never
    writes a ledger row.
  - Every entry records `source_type` + `source_id` for traceability.
  - Deleting a source transaction reverses its entry in the same transaction
    (`reverseSource()`), so no orphan row is left.
  - A hand-recorded entry (`source_type = manual`) may be deleted from the UI; a
    **generated entry cannot** — its source must be removed instead.
  - `category` must belong to the chosen `entry_type` (validated in the request).
- **Permissions:** `ledger.view`, `ledger.create`, `ledger.delete`, `finance.view`
- **UI:** `resources/views/ledger/` (`index`, `transactions`, `create`)
- **Notes:** because the Sales and Finance modules that will normally write
  entries are not built yet, the ledger supports **manual entries** today — making
  the module genuinely usable rather than an empty shell. Those modules will call
  the same `PondLedgerService::record()` when they land. The pond details page now
  shows real income / expense / profit from this ledger.

## 4. Food (Feed) Management — ✅ (Phase 4)

- **Purpose:** feed product catalogue, feed stock, purchasing, usage and manual
  adjustments.
- **Routes:** `feed.index`, `feed.stock`,
  `feed.types.{index,create,store,edit,update,destroy}`,
  `feed.purchases.{index,create,store,destroy}`,
  `feed.usages.{index,create,store,destroy}`,
  `feed.adjustments.{index,create,store,destroy}`
- **Controllers:** `Feed/FeedController` (dashboard + stock page),
  `Feed/FeedTypeController`, `Feed/FeedPurchaseController`,
  `Feed/FeedUsageController`, `Feed/FeedAdjustmentController`
- **Services:** `Feed/FeedStockService` — the ONE definition of feed stock
  (`purchases + adjustments-in − usage − adjustments-out`), the non-negative
  guard, derived line cost, the low-stock signal and all writes;
  `Feed/FeedTypeService` — feed type writes + in-use delete guard
- **Requests:** `Feed/{Store,Update}FeedTypeRequest`, `Feed/StoreFeedPurchaseRequest`,
  `Feed/StoreFeedUsageRequest`, `Feed/StoreFeedAdjustmentRequest`
- **Policies:** `FeedTypePolicy`, `FeedPurchasePolicy`, `FeedUsagePolicy`,
  `FeedStockAdjustmentPolicy`
- **Tables:** `feed_types`, `feed_purchases`, `feed_usages`,
  `feed_stock_adjustments` ✅
- **Business rules:** (docs/BUSINESS_LOGIC.md §2 and §5a)
  - Purchase → stock IN; usage → stock OUT; adjustment → IN/OUT **with a reason**.
    Stock is never typed in directly — it is always derived from movements.
  - **Feed stock can never go negative.** Enforced twice: in the FormRequest
    (clean field message) and in `FeedStockService` on a row-locked feed type.
  - Feed is measured in **kg**, rounded to 3 decimals; `total_cost` is DERIVED
    (`quantity_kg × unit_cost`), never entered twice.
  - An adjustment **always** records a reason (key from `config/feed.php`).
  - Stock at or below `feed_types.low_stock_level_kg` reads as low — surfaced on
    the dashboard and the type list (`FeedStockService::isLow`).
  - A feed type used by any purchase/usage/adjustment **cannot be deleted**
    (`restrictOnDelete` FK + `FeedTypeService` guard); mark it inactive instead.
  - Deleting a purchase/adjustment-IN that would take stock negative is refused.
- **Permissions:** `feed.view`, `feed.purchase`, `feed.usage`, `feed.adjust`,
  `feed.type.manage`
- **UI:** `resources/views/feed/` (`index`, `types/*`, `purchases/*`,
  `usages/*`, `adjustments/*`)
- **Notes:** there is no `suppliers` table yet, so a purchase stores the supplier as
  a plain `supplier_name` string (same approach as `fish_stockings`). A future
  Suppliers module will add a nullable `supplier_id` FK alongside it.

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

## 6. Fish Stock — ✅ (Phase 3)

- **Purpose:** species catalogue, stocking (stock IN), mortality (stock OUT) and
  harvest (stock OUT).
- **Routes:** `fish.index`, `fish.species.{index,create,store,edit,update,destroy}`,
  `fish.stockings.{index,create,store,destroy}`,
  `fish.mortalities.{index,create,store,destroy}`,
  `fish.harvests.{index,create,store,destroy}`
- **Controllers:** `Fish/FishStockController` (dashboard),
  `Fish/FishSpeciesController`, `Fish/FishStockingController`,
  `Fish/FishMortalityController`, `Fish/HarvestController`
- **Services:** `Fish/FishStockService` — the ONE definition of live stock
  (`stocked − mortality − harvested`), the non-negative guard, derived weights and
  all writes; `Fish/FishSpeciesService` — species writes + in-use delete guard
- **Requests:** `Fish/{Store,Update}FishSpeciesRequest`, `Fish/StoreFishStockingRequest`,
  `Fish/StoreFishMortalityRequest`, `Fish/StoreHarvestRequest`
- **Policies:** `FishSpeciesPolicy`, `FishStockingPolicy`, `FishMortalityPolicy`,
  `HarvestPolicy`
- **Tables:** `fish_species`, `fish_stockings`, `fish_mortalities`, `harvests` ✅
- **Business rules:** (docs/BUSINESS_LOGIC.md §2 and §10)
  - Stocking → stock IN; mortality/harvest → stock OUT. Stock is never typed in
    directly — it is always derived from movement records.
  - **Stock can never go negative.** Enforced twice: in the FormRequest (clean
    field message) and in `FishStockService` on a row-locked pond (the write path).
  - Derived weights are computed by the service (total kg = count × avg g / 1000)
    and stored; `null` weights render as `—`, never `0`.
  - A species used by any stocking/harvest **cannot be deleted**
    (`restrictOnDelete` FK + `FishSpeciesService` guard); mark it inactive instead.
  - A pond holding live fish (or with any movement history) **cannot be deleted**;
    `PondService` refuses it with an explanation.
- **Permissions:** `fish.view`, `fish.stock`, `fish.mortality`, `fish.harvest`,
  `fish.species.manage`
- **UI:** `resources/views/fish/` (`index`, `species/*`, `stockings/*`,
  `mortalities/*`, `harvests/*`)

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

## 11. Finance — ✅

- **Purpose:** income, expenses and categories.
- **Routes:** `finance.income.*`, `finance.expenses.*`, `finance.categories.*`
- **Tables:** `income_entries`, `expense_categories`, `expense_entries`
- **Services:** `Services/Finance/LedgerRules` ✅
- **Business rules:** `Net Profit = Income − Expense`; losses are displayed as
  losses, never clamped to zero; percentages guard against a zero base.
- **Permissions:** `finance.view`, `income.*`, `expense.*`,
  `expense.category.manage`, `ledger.view`
- **UI:** `resources/js/react/Pages/Finance/{Income,Expenses,Categories}`

## 12. Reports — ✅

- **Purpose:** the nine reports, all with date range, search, filters,
  pagination, summary, table, print-ready layout and CSV export.
- **Routes:** `reports.sales`, `reports.purchases`, `reports.feed`,
  `reports.fish-stock`, `reports.ponds`, `reports.fcr-growth`,
  `reports.income`, `reports.expenses`, `reports.profit-loss`, `reports.export`
- **Tables:** reads across all modules — owns none
- **Services:** `App\Http\Controllers\Reports\ReportController` shapes one contract;
  totals come from the domain services (`FinanceService`, `FishStockService`,
  balance services). Report calculation logic is never duplicated.
- **Permissions:** `reports.view`, `reports.export`, `reports.financial`
- **UI:** `resources/js/react/Pages/Reports/**`

## 13. Notifications — ✅

- **Purpose:** database-driven alerts, generated from REAL ERP events.
- **Tables:** `erp_notifications`
- **Services:** `Services/Notification/NotificationService`
- **Types:** low feed stock, critical feed stock, feeding scheduled, feeding due,
  feeding completed, sale/purchase/payment/income/expense recorded,
  stocking/mortality/harvest, inspection and growth. Low-feed, critical-feed and
  feeding-due alerts are idempotent per day (duplicate-proof).
- **Proactive trigger:** the low/critical feed checks and the feeding-due check run
  when the Dashboard or the Food Dashboard is opened, so alerts appear without the
  user having to visit the specific module first.
- **UI:** the header bell (`Components/NotificationBell.jsx`).

## 18. Fish Batches / Cycles — ✅

- **Purpose:** group a pond's stocking/feeding/growth/harvest into a named cycle so
  its yields and survival can be reported per cycle.
- **Routes:** `fish.batches.*` (`index`, `create`, `store`, `show`, `edit`,
  `update`, `destroy`)
- **Tables:** `fish_batches` (+ a nullable `fish_batch_id` on `fish_stockings`,
  `fish_mortalities`, `harvests` — additive, existing rows keep working).
- **Source-of-truth rule:** the batch stores NO running quantity. Its current
  quantity is always derived from the movement records tagged to it
  (`Σ stockings − Σ mortalities − Σ harvests`, FishBatchService). Surival = current
  ÷ initial × 100.
- **Flow:** create batch → opening stocking recorded through FishStockService →
  the batch becomes active → mortality/harvest/growth tagged to it → close it.
- **Permissions:** `fish.batch.view`, `fish.batch.manage`.
- **UI:** `resources/js/react/Pages/Fish/Batches/**`.

## 19. Feeding (Meal) Schedules — ✅

- **Purpose:** plan a pond's meals and record what was actually fed.
- **Routes:** `feed.schedules.*` (plans), `feed.feedings.*` (actual meals).
- **Tables:** `feeding_schedules` (plans), `feedings` (actual meals).
- **THE KEY RULE:** a schedule is a PLAN and NEVER moves feed stock. Recording an
  actual feeding writes a `feed_usages` row through FeedStockService, which is the
  single authority for feed stock — so one authority owns the figure.
- **Ledger integration:** a feeding consumes feed stock and feeds FCR; deleting it
  restores the stock (the usage row it produced).
- **Notifications:** scheduled / due / completed, from real schedules.
- **Permissions:** `feed.schedule.manage`, `feed.feeding`.
- **UI:** `resources/js/react/Pages/Feed/Schedules/**`, `Feed/Feedings/**`.

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