# Working Flow — Fish Farm ERP

How a request actually travels through the running application, end to end, with
real file paths. This is the **runtime** companion to `docs/ARCHITECTURE.md` (which
defines _what belongs where_). If anything here disagrees with the code, the code
wins — then update this file.

---

## 1. The one flow every page follows

```
Browser
  │  user clicks an Inertia <Link> or submits a useForm() form
  ▼
React page (resources/js/react/Pages/**)
  │  router.get/post/put/delete  →  XHR with the `X-Inertia` header
  ▼
Laravel route (routes/web.php)
  ▼
Middleware stack
  │  web  →  auth  →  permission:<key>  →  HandleInertiaRequests
  ▼
Controller (app/Http/Controllers/**)
  │  authorizes (Gate::authorize), validates (FormRequest), delegates
  ▼
Service (app/Services/**)        ← THE business logic + aggregate queries
  ▼
Model / Query (app/Models/**)
  ▼
MySQL / MariaDB
  │
  ▼
Inertia response  Inertia::render('Page/Name', [ …props ])
  │  or a redirect for writes (App\Support\AsyncResponse)
  ▼
React re-renders with the new props — the shell never remounts
```

**Key rule:** every business number is computed in a Laravel service and travels as
a prop. React formats and displays it; React never invents a total.

### Writes redirect, they do not return JSON

`App\Support\AsyncResponse::ok()` returns a redirect for normal **and** Inertia
requests. It deliberately ignores the `X-Inertia` header (`wants()` returns `false`
when `X-Inertia` is present) because Inertia needs the 302 to perform its visit —
returning JSON would make the page silently stay put. (The legacy Blade async layer
sent `X-Requested-With` _without_ `X-Inertia`, so it still gets JSON.)

---

## 2. Worked example — recording a Sale

```
Sales/Index.jsx   "+ New Sale"  ── <Button href={routes['sales.create']}>  (Inertia <Link>)
   ▼
GET /fish-farm/sales/create      (routes/web.php → SaleController@create, permission:sales.create)
   ▼
SaleController@create
   │  Gate::authorize('create', Sale::class)
   │  Customer::query()->orderBy('name')->pluck('name','id')  → options.customerOptions
   │  …pond / species / feed options, all real queries
   ▼
Inertia::render('Sales/Create', ['options' => ['customerOptions' => …]])
   ▼
Sales/SaleForm.jsx   <Select name="customer_id" options={options.customerOptions} />
   │  useForm({ customer_id, sale_date, items: [...] })
   ▼  submit
POST /fish-farm/sales             (SaleController@store, permission:sales.create)
   ▼
StoreSaleRequest   validate: customer_id required + Rule::exists(Customer::class,'id')
   │  derived values (total, due, status, line_total) are NOT accepted from input
   ▼
SalesService::create($data)       (app/Services/Sales/SalesService.php)
   │  writes sales + sale_items in one transaction, computes every total
   ▼
sales / sale_items tables         (money stays NUMERIC)
   ▼
AsyncResponse::ok($request, 'Sale recorded.', 'sales.list')
   │  Inertia request → 302 → Inertia follows it → fresh props
   ▼
Sales/Index.jsx   the new sale appears; the layout never remounted (no full reload)
```

The same shape holds for every relationship:

| Field                              | Options come from                          | Stored in                                                 | Displayed as         |
| ---------------------------------- | ------------------------------------------ | --------------------------------------------------------- | -------------------- |
| Sale → customer                    | `customers` table                          | `sales.customer_id`                                       | `customer` (name)    |
| Purchase → supplier                | `suppliers` table                          | `purchases.supplier_id`                                   | `supplier` (name)    |
| Stocking → pond / species          | `ponds` / `fish_species`                   | `fish_stockings.pond_id`, `.fish_species_id`              | pond + species names |
| Feed usage → feed type / pond      | `feed_types` / `ponds`                     | `feed_usages.feed_type_id`, `.pond_id`                    | type + pond names    |
| Inspection → pond                  | `ponds`                                    | `inspections.pond_id`                                     | pond name            |
| Income / Expense → category / pond | DB / `config('finance.income_categories')` | `income_entries.*`, `expense_entries.expense_category_id` | labels               |

**Dropdown rules:** every option list is a real query (`pluck` / `mapWithKeys`) on
the model. Only genuine enumerations (pond statuses, feed adjustment reasons, income
categories, payment methods) come from `config/*.php` — that is intentional config,
not fake data.

---

## 3. Module relationships

```
Customer ─hasMany─> Sale ─hasMany─> SaleItem
Customer ─hasMany─> CustomerPayment
Supplier ─hasMany─> Purchase ─hasMany─> PurchaseItem
Supplier ─hasMany─> SupplierPayment
Party    ─hasMany─> PartyTransaction

Pond ─hasMany─> FishStocking | FishMortality | Harvest | FeedUsage | Inspection | GrowthRecord | PondLedgerEntry
Pond ─belongsTo─> PondType

FeedType ─hasMany─> FeedPurchase | FeedUsage | FeedStockAdjustment

IncomeEntry ─belongsTo─> Pond (optional)          ExpenseEntry ─belongsTo─> ExpenseCategory, Pond

FCR / Growth   read Feed + Growth + Stocking data to derive FCR (never stored)
Reports        read every module through the services; they own no tables
```

Every derived figure — due balance, profit, FCR, live stock, feed stock — is
**computed** from the movement rows, never stored. That is what keeps the numbers
from drifting.

---

## 4. Permissions chain

```
User ─belongsToMany─> Role ─belongsToMany─> Permission
  │
  ▼  every protected route carries `permission:<key>` middleware
  ▼  every controller re-asserts the policy  (Gate::authorize)
  ▼  FormRequest::authorize() is the third check
  ▼
React `can()` / usePermission() is DISPLAY ONLY — it hides a button, it is not security.
```

Shared props expose the user's permission names so the sidebar and buttons can hide
themselves (`auth.user.permissions`). Laravel middleware and policies remain the
only real enforcement.

---

## 5. Currency

- **One source:** `App\Support\Currency` reads `config('fishfarm.currency_code' | 'currency_symbol' | 'currency_decimals')`. Nothing else reads the config directly.
- The symbol travels to React as the shared `currency` prop (`{ code, symbol, decimals }`).
- **One formatter:** `money(value)` in `resources/js/react/Components/Page.jsx`. `setCurrencySymbol()` applies the configured symbol once in `resources/js/app.jsx`.
- **The database stores a bare number** (`12600.00`); only the UI renders `৳12,600.00`.
- `App\Support\Money::format()` mirrors `money()` for any server-side string, so PHP and JS produce identical output (`৳-19,155.00` — sign before the digits, no space).

> Config note: `currency_symbol` **must** be a double-quoted `"\u{09F3}"`. A
> single-quoted `'\u{09F3}'` is the literal text `\u{09F3}` (PHP only expands
> `\u{...}` in double quotes), which is what leaked into the UI as the escaped
> string. This is the canonical example of a config typo that no amount of React
> fixing could repair.

---

## 6. Reports & filters

```
ReportShell.jsx  → ReportFilters (date from/to + extra selects)  → router.get(url, params)
                                    │
                                    ▼  preserves the query string in the URL
Reports\ReportController          one controller, nine reports, identical contract:
   range()   → from/to (defaults to this month; reversed ranges are swapped)
   query     → real rows, eager-loaded relations, filtered + paginated
   totals    → real aggregates (SUM over the same range)
   export    → /fish-farm/reports/{report}/export streams a CSV of the SAME filters
```

Filters live in the URL, so back/refresh/bookmarks keep them. The CSV export is a
plain `<a>` download (a genuine browser navigation — CSV is not an Inertia visit).

---

## 7. Adding a new module

```
1. Migration      database/migrations/*_create_x_table.php   (additive only)
2. Model          app/Models/X.php  (fillable, casts, relationships, scopes, isDeletable)
3. Form Request   app/Http/Requests/<Area>/{Store,Update}XRequest.php  (rules + authorize)
4. Service        app/Services/<Area>/XService.php   ← ALL business logic here
5. Policy         app/Policies/XPolicy.php  (+ register)
6. Controller     app/Http/Controllers/<Area>/XController.php  (thin; Inertia::render / AsyncResponse)
7. Routes         routes/web.php  (permission:* middleware + name; literals BEFORE {wildcards})
8. React pages    resources/js/react/Pages/<Area>/{Index,Create,Edit}.jsx
9. Shared form    resources/js/react/Pages/<Area>/XForm.jsx  (used by create + edit)
10. Permissions   config/permissions.php (+ seeder) and config/navigation.php
11. Nav entry     config/navigation.php + the mirror resources/js/react/navigation.js
12. Docs          update docs/MODULES.md + docs/ROUTES.md + docs/CHANGELOG.md
```

List pages follow one layout: **title + `+ Add New` (Inertia link to the create
page) → search/filters → table**. Do not put a large create form on top of a list
page; the Pond-Ledger "record + history" workspace is the one intentional exception.

---

## 8. Conventions that keep it working

- **Thin controllers.** No query-building beyond trivial lookups; no maths.
- **One write path per domain.** All fish-stock movements go through `FishStockService`; all feed-stock movements through `FeedStockService`; all pond-ledger entries through `PondLedgerService`.
- **Derived values are derived.** Dues, profit, FCR, fish stock, feed stock, biomass, survival and batch quantity are computed, never stored.
- **Prefer eager counts over per-row queries.** `isDeletable()` reads `withCount(...)` attributes when present and only falls back to a live query (avoids an N+1 on list pages).
- **Money is numeric end-to-end**; formatted only by `money()`.
- **Never fabricate data.** No data ⇒ an empty state, not a placeholder figure.
- **Confirmations use the one global dialog.** `useConfirm()` (backed by `ConfirmProvider` in `AppLayout` + `ConfirmModal`) — never `window.confirm` or `alert`.
- **Authorization is server-side.** The React `can()` check is cosmetic.

---

## 9. Real end-to-end examples

Each of these is the complete path: React → Inertia → route → middleware → policy →
controller → FormRequest → service → model → MySQL → Inertia → React.

### Create a Sale
```
Sales/Index.jsx  →  <Button href={routes['sales.create']}>  (Inertia <Link>)
GET  /fish-farm/sales/create          SaleController@create   (permission:sales.create)
POST /fish-farm/sales                 SaleController@store
  StoreSaleRequest  (customer_id exists, derived totals NOT accepted)
  SalesService@create  →  Sale + SaleItem rows, totals computed
  AsyncResponse::ok(..., 'sales.list')  →  302 → fresh list props
```

### Create a Fish Batch
```
Fish/Batches/Create.jsx  →  BatchForm  →  useForm().post(routes['fish.batches.store'])
POST /fish-farm/fish/batches          FishBatchController@store  (permission:fish.batch.manage)
  StoreFishBatchRequest
  FishBatchService@create
    ├─ inserts the `fish_batches` row (code auto BATCH-000N)
    └─ records the opening stocking through FishStockService (a REAL stock row),
       tagged to the batch — so the batch owns no quantity of its own
  → redirect to /fish-farm/fish/batches/{id}
```

### Record a Feeding (consumes feed stock)
```
Feed/Feedings/Index.jsx board row  →  "Record"  →  /feed/feeding/new?schedule={id}
POST /fish-farm/feed/feeding          FeedingController@feedingStore  (permission:feed.feeding)
  StoreFeedingRequest  (non-skipped meals must have consumed > 0)
  FeedingService@recordFeeding   (ONE DB transaction)
    └─ FeedStockService@recordUsage   → a `feed_usages` row (stock DOWN, row-locked guard)
       then inserts the `feedings` row linking `feed_usage_id`
  NotificationService@feedingCompleted
  → redirect to /fish-farm/feed/feeding   (feed stock is now lower; FCR will use it)
Deleting the feeding restores the stock (deleteOutMovement on the same usage row).
```

> **Scheduling ≠ consuming.** Creating a schedule (`POST /fish-farm/feed/feeding/schedules`)
> writes only a `feeding_schedules` row. No stock moves until a feeding is recorded.

### Record Mortality
```
Fish/Mortalities/Create.jsx  →  POST /fish-farm/fish/mortalities   (permission:fish.mortality)
  StoreFishMortalityRequest  →  FishStockService@recordMortality
    └─ pond row-locked, guardAgainstNegative() refuses an overdraft, then inserts
  → live stock, survival and biomass fall; the FCR inputs adjust accordingly
```

### Record a Harvest
```
Fish/Harvests/Create.jsx  →  POST /fish-farm/fish/harvests   (permission:fish.harvest)
  StoreHarvestRequest  →  FishStockService@recordHarvest  (avg_weight_g derived from kg)
  → live stock falls; the batch (if one is tagged) reflects it
```

### Receive a Customer Payment (with an account)
```
Customers/Payments/Create.jsx  →  POST /fish-farm/customers/payments  (permission:customer.payment.create)
  StoreCustomerPaymentRequest
  CustomerPaymentController@store   — ONE DB transaction:
    ├─ inserts `customer_payments`
    └─ CustomerBalanceService@syncSale   (sale paid/due/status realigned)
  → the customer's due falls and the linked sale's settlement state stays correct
```

### Pay a Supplier
```
Suppliers/Payments/Create.jsx  →  POST /fish-farm/suppliers/payments  (permission:supplier.payment.create)
  SupplierPaymentController@store   — ONE DB transaction:
    ├─ inserts `supplier_payments`
    └─ SupplierBalanceService@syncPurchase
```

### Record an Expense / Income
```
Finance/Expenses/Create.jsx  →  POST /fish-farm/finance/expenses  (permission:expense.create)
  → ExpenseEntry stored; when a pond is chosen the money is also attributed to the
    pond ledger (PondLedgerService), so per-pond profit stays correct.
```
