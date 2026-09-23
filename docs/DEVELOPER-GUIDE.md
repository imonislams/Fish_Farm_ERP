# Developer Guide — Fish Farm ERP

How the application actually works today (Laravel + Inertia + React), with real file paths.
If anything here disagrees with the code, **the code wins** — then update this file.

---

## 1. The real data flow

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
  │  authorizes, validates (FormRequest), delegates — no business maths
  ▼
Service (app/Services/**)
  │  THE business logic + aggregate queries
  ▼
Model / Query (app/Models/**)
  ▼
MySQL / MariaDB
  │
  ▼
Inertia response (Inertia::render('Page/Name', [ …props ]))
  │  or a redirect for writes (AsyncResponse)
  ▼
React re-renders with the new props — the layout/shell never remounts
```

**Key rule:** every business number is computed in a Laravel service and travels as a
prop. React formats and displays it; React never invents a total.

### Why writes return a _redirect_, not JSON

`App\Support\AsyncResponse::ok()` returns a redirect for normal **and** Inertia requests.
It deliberately ignores the `X-Inertia` header (`wants()` returns `false` when `X-Inertia`
is present) because Inertia needs the 302 to perform its visit — returning JSON makes the
page silently stay put. (The Blade-era async layer sent `X-Requested-With` _without_
`X-Inertia`, so it still gets JSON.)

---

## 2. Field data flow — a worked example

### Sale → Customer (`resources/js/react/Pages/Sales/SaleForm.jsx`)

```
customers table
   ▼  Customer::query()->orderBy('name')->pluck('name','id')
SalesController@create  →  Inertia::render('Sales/Create', ['options' => ['customerOptions' => …]])
   ▼  props.options.customerOptions
SaleForm.jsx  <Select name="customer_id" options={options.customerOptions} />
   ▼  useForm data.customer_id
POST /fish-farm/sales   (routes/web.php → SaleController@store)
   ▼  StoreSaleRequest validation (customer_id: required, exists:customers,id)
SalesService::create()   (app/Services/Sales/SalesService.php)
   ▼  Sale::fill(['customer_id' => …])->save()
sales.customer_id
   ▼  list reload → SalesController@index
Inertia props: sales.data[].customer = $sale->customer?->name
   ▼
Sales/Index.jsx  renders the real customer name
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

**Dropdown rules:** every option list is a real query (`pluck`/`mapWithKeys`) on the model.
Only genuine enumerations (pond statuses, feed adjustment reasons, income categories,
payment methods) come from `config/*.php` — that is intentional config, not fake data.

---

## 3. Where things live

| Concern                          | Location                                                     |
| -------------------------------- | ------------------------------------------------------------ |
| React pages                      | `resources/js/react/Pages/<Module>/<Page>.jsx`               |
| Shared React UI                  | `resources/js/react/Components/*`                            |
| The shell (sidebar + header)     | `resources/js/react/Layouts/AppLayout.jsx`                   |
| Page → layout binding            | `withLayout()` in `resources/js/react/Components/Page.jsx`   |
| Money/number/date format         | `money()`, `num()`, `date()` in `Components/Page.jsx`        |
| Confirmation dialog (global)     | `useConfirm()` in `Components/ConfirmModal.jsx` (provider in `Layouts/AppLayout.jsx`) |
| Currency source (PHP)            | `App\Support\Currency` — the **only** reader of the currency config |
| Permission helper (display only) | `usePermission()` in `Components/Page.jsx`                   |
| Inertia entry + currency boot    | `resources/js/app.jsx`                                       |
| Inertia root template            | `resources/views/app.blade.php`                              |
| Shared props                     | `app/Http/Middleware/HandleInertiaRequests.php`              |
| Route → URL map for React        | `navigationRoutes()` + `EXTRA_ROUTES` in the same middleware |

React never hard-codes a URL: it reads `props.routes['ponds.index']`. Add a name to
`EXTRA_ROUTES` only when a page needs a route that is **not** in `config/navigation.php`.

---

## 4. Adding a new module

```
1. Migration           database/migrations/*_create_x_table.php   (additive only)
2. Model               app/Models/X.php  (fillable, casts, relationships, scopes)
3. Form Request        app/Http/Requests/<Area>/{Store,Update}XRequest.php  (rules + authorize)
4. Service             app/Services/<Area>/XService.php   ← ALL business logic here
5. Policy              app/Policies/XPolicy.php  (+ register in AppServiceProvider)
6. Controller          app/Http/Controllers/<Area>/XController.php  (thin; Inertia::render / AsyncResponse)
7. Routes              routes/web.php  (permission:* middleware + name)
8. React page(s)       resources/js/react/Pages/<Area>/{Index,Create,Edit}.jsx
9. Shared form         resources/js/react/Pages/<Area>/XForm.jsx  (used by create + edit)
10. Permissions         config/permissions.php  (+ seeder) and config/navigation.php
11. Nav entry          config/navigation.php  (keeps the sidebar + React nav mirror in sync)
12. Notifications      emit from a model observer if the module raises real events
13. Docs               update docs/MODULES.md + docs/ROUTES.md + docs/CHANGELOG.md
```

List pages follow one layout: **title + `+ Add New` → dedicated create page → search/filters → table**.
Do not put a large create form on top of a list page (the Pond-Ledger "record + history"
workspace is the one intentional exception).

---

## 5. Conventions that keep it working

- **Thin controllers.** No query-building beyond trivial lookups; no maths.
- **One write path per domain.** e.g. all fish-stock movements go through
  `FishStockService`; all ledger entries through `PondLedgerService`.
- **Derived values are derived.** Dues, profit, FCR and stock are computed, never stored.
- **Money is numeric end-to-end**; formatted only by `money()`.
- **One currency source.** `App\Support\Currency` is the only place that reads
  `config('fishfarm.currency_*')`; everything else (shared props, controllers,
  `Money::format()`) goes through it. `currency_symbol` must be a **double-quoted**
  `"\u{09F3}"` — single quotes make PHP emit the literal text `\u{09F3}`.
- **One confirmation dialog.** `useConfirm()` (global `ConfirmProvider`, backed by
  `ConfirmModal`) — never `window.confirm`/`alert`.
- **Never fabricate data.** No data ⇒ empty state, not a placeholder figure.
- **Authorization is server-side** (`permission:*` middleware + policies). The React
  `can()` check is cosmetic.

---

## 6. QA & verification

There is no formal test suite (per project policy). Verify manually:

```bash
npm run build                       # must pass
php artisan serve --host=0.0.0.0 --port=8000
```

Then walk the module: create a record → confirm it is in the list and in the database →
edit it → confirm the form pre-fills the real value → filter/search → confirm the backend
query changes the rows. Watch the browser console for errors and the network tab for
failed requests.

The dashboard's date filter (`/dashboard?range=this_month|today|…`) and the header bell
(unread count, mark-read) are good end-to-end smoke tests of the Inertia flow.
