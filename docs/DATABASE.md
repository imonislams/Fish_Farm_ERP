# Database — Fish Farm ERP

Read `docs/PROJECT.md` and `docs/ARCHITECTURE.md` first.

---

## 1. Architecture decision: ONE COMPANY (Version 1)

**Version 1 is for a single company / single fish farm.** The application is
**not** multi-tenant and must not be built as if it were.

- There is **one** `companies` row. It is the business identity.
- **No `farm_id` / `company_id` foreign key is added to business tables.** The
  company is singular and implicit — repeating a foreign key on every table would
  add join cost and noise for a constant value.
- **No tenant middleware. No tenant switching. No company selector. No
  multi-tenant permissions. No SaaS billing/subscription logic.**

In other words: `users` belong to the company
(`companies → users → roles & permissions`), and **business data belongs to the
same single company context** — it is simply not keyed by a company column.

### Future multi-company support

If a future **major** version needs multi-company support, the migration path is
well-understood and deliberately left open:

1. Add a `company_id` column to the business tables (nullable → backfill → NOT
   NULL).
2. Add a global scope / trait to constrain reads.
3. Add company switching and per-company permissions.

Nothing in the current design *prevents* this. We are simply not paying the
complexity cost now. **Do not pre-emptively add these columns or traits.**

## 2. Current state of the database

**Phase 1 has been applied.** All six migrations exist and have been run against
MySQL (`fish_farm_erp`):

| Migration file                                                    | Effect                                                    |
| ----------------------------------------------------------------- | --------------------------------------------------------- |
| `0001_01_01_000_create_users_table.php`                        | `users`, `password_reset_tokens`, `sessions`                |
| `0001_01_01_000001_create_cache_table.php`                        | `cache`, `cache_locks`                                      |
| `0001_01_01_000002_create_jobs_table.php`                         | `jobs`, `job_batches`, `failed_jobs`                        |
| `0001_01_01_000003_create_companies_table.php`                    | `companies`                                                 |
| `0001_01_01_000004_add_company_id_and_is_active_to_users_table.php`| adds `users.company_id` (FK) and `users.is_active`         |
| `0001_01_01_000005_create_roles_and_permissions_tables.php`       | `roles`, `permissions`, `permission_role`, `role_user`      |
| `2026_09_22_000001_create_pond_types_table.php`                   | `pond_types` (Phase 2)                                      |
| `2026_09_22_000002_create_ponds_table.php`                        | `ponds` (Phase 2)                                           |
| `2026_09_23_000001_create_fish_species_table.php`                 | `fish_species` (Phase 3)                                    |
| `2026_09_23_000002_create_fish_stockings_table.php`               | `fish_stockings` (Phase 3)                                  |
| `2026_09_23_000003_create_fish_mortalities_table.php`             | `fish_mortalities` (Phase 3)                                |
| `2026_09_23_000004_create_harvests_table.php`                     | `harvests` (Phase 3)                                        |
| `2026_09_24_000001_create_feed_types_table.php`                   | `feed_types` (Phase 4)                                      |
| `2026_09_24_000002_create_feed_purchases_table.php`               | `feed_purchases` (Phase 4)                                  |
| `2026_09_24_000003_create_feed_usages_table.php`                  | `feed_usages` (Phase 4)                                     |
| `2026_09_24_000004_create_feed_stock_adjustments_table.php`       | `feed_stock_adjustments` (Phase 4)                          |
| `2026_09_25_000001_create_pond_ledger_entries_table.php`          | `pond_ledger_entries` (Phase 5)                             |

**Phases 2, 3, 4 and 5 have been applied.** The business tables — `pond_types`,
`ponds`, `fish_species`, `fish_stockings`, `fish_mortalities`, `harvests`,
`feed_types`, `feed_purchases`, `feed_usages`, `feed_stock_adjustments`,
`pond_ledger_entries` — exist. No destructive command was used: the `users`
change was an incremental `ALTER TABLE`, and the Phase 2–5 migrations only
`CREATE` new tables.

### Seeded content
| Seeder                | Creates                                              | Rows |
| --------------------- | ---------------------------------------------------- | ---- |
| `PermissionSeeder`    | the 70-key permission catalogue                      | 70   |
| `RoleSeeder`          | the 7 system roles + their permission grants         | 7    |
| `AdminUserSeeder`     | the single company and the first Super Admin         | 1 + 1|

All three are **idempotent** (`updateOrCreate` + `sync`). No business data is
seeded — the ERP database stays empty until real records are created.

### Demo / sample data (opt-in — NOT part of `db:seed`)

`DemoDataSeeder` creates **fake business data** (ponds, species, feed types,
stocking/mortality/harvest/transfer, feed purchases/usage/adjustment and pond
ledger entries) so the UI can be checked by hand.

It is **deliberately NOT listed in `DatabaseSeeder`**, so `php artisan db:seed`
never creates fake records. Run it explicitly, on a database you are happy to
fill with samples:

```powershell
# create sample data
php artisan db:seed --class=DemoDataSeeder

# remove everything it created
php artisan db:seed --class=DemoDataSeeder --command=remove
```

Notes:
- Every record is written through the **real services** (`FishStockService`,
  `FeedStockService`, `PondLedgerService`), so the business rules run exactly as
  they would from the UI.
- All names are prefixed `Demo — ` so the rows are obvious and easy to find.
- `remove()` deletes only the demo rows, children before parents.
- One feed type (Finisher Feed) is stocked *below* its reorder level on purpose,
  so the low-stock state is visible; one pond (N-02) has **no** ledger entries so
  the empty state is visible.

### Environment prerequisite
1. Start MySQL/MariaDB in XAMPP.
2. Create the database:
   `CREATE DATABASE fish_farm_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
3. Run `php artisan migrate` (incremental) then `php artisan db:seed`.

`.env` is pointed at `mysql` / `fish_farm_erp`. Session, cache and queue are set
to `file` / `sync` so the application boots even before MySQL is started.

For the first administrator, set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env`
before seeding — otherwise a random password is generated and printed once. See
`docs/PROJECT.md` §14 and `docs/PERMISSIONS.md` §7a.

### Migration safety check

`php artisan migrate:status` must report **no Pending migrations** before the UI
is exercised. A feature whose migration has not been run fails at the first
query (for example, the Pond Ledger timeline reads `reference` columns added by
`2026_09_26_000002_add_reference_to_stockings_and_mortalities` — until that
migration runs, the ledger and transfers pages error).

### Safety rules

- Migrations are **incremental** and reversible.
- **Never** run `migrate:fresh`, `migrate:refresh` or `db:wipe` unless the user
  explicitly asks.
- Never drop or rewrite an existing table to "fix" a schema; add a migration.

## 3. Identity & access tables (implemented in this phase)

### companies
- **Purpose:** the single business identity of the farm. Branding, contact
  details and locale/currency defaults all live here.
- **Columns:** `id`, `name`, `code` (unique, short slug), `logo` (nullable path),
  `phone`, `email`, `address`, `currency` (default `BDT`), `timezone`
  (default `Asia/Dhaka`), `status` (`active` | `inactive`), `timestamps`.
- **Relationships:** hasMany `users`.
- **Rules:** exactly one row in Version 1. The Settings module edits it. Never
  hard-delete it; set `status = inactive` instead.

### users
- **Purpose:** authentication and attribution of every action.
- **Columns:** `id`, `company_id` (FK → `companies`, cascade on delete),
  `name`, `email` (unique), `email_verified_at`, `password`, `remember_token`,
  `is_active` (bool, default true), `timestamps`.
- **Relationships:** belongsTo `companies`; belongsToMany `roles`.
- **Ownership:** a user belongs to **the** company.
- **Rules:** never hard-delete a user who has records; set `is_active = false`.
  An inactive user cannot authenticate.

### roles
- **Purpose:** named permission bundles.
- **Columns:** `id`, `name` (unique, machine key e.g. `farm_admin`), `label`
  (human e.g. "Farm Admin"), `description` (nullable), `is_system` (bool —
  protects seeded roles from deletion), `timestamps`.
- **Relationships:** belongsToMany `permissions`; belongsToMany `users`.
- **Rules:** seeded roles (see §5) have `is_system = true` and are not deletable
  from the UI.

### permissions
- **Purpose:** granular capability keys (`pond.view`, `feed.purchase`, …).
- **Columns:** `id`, `name` (unique, `resource.action`), `group` (module, for
  grouped display in the UI), `label`, `timestamps`.
- **Relationships:** belongsToMany `roles`.
- **Rules:** permission names are **stable identifiers**. Renaming one requires a
  migration and a CHANGELOG entry. See `docs/PERMISSIONS.md`.

### role_user
- **Purpose:** pivot — which user holds which role.
- **Columns:** `id`, `user_id` (FK, cascade), `role_id` (FK, cascade),
  `timestamps`. Unique on `(user_id, role_id)`.

### permission_role
- **Purpose:** pivot — which role grants which permission.
- **Columns:** `id`, `permission_id` (FK, cascade), `role_id` (FK, cascade),
  `timestamps`. Unique on `(permission_id, role_id)`.

## 4. Business entities

The full intended ERP model. `pond_types`, `ponds` (Phase 2); `fish_species`,
`fish_stockings`, `fish_mortalities`, `harvests` (Phase 3); `feed_types`,
`feed_purchases`, `feed_usages`, `feed_stock_adjustments` (Phase 4); and
`pond_ledger_entries` (Phase 5) are **implemented**; the rest is the **plan**, not
yet in the schema. Build it incrementally, one module at a time, keeping
`docs/MODULES.md` in step.

**Note: no `farm_id` / `company_id` column appears on any of these tables.** The
single company is implicit. Tables reference their *domain* parent (a pond, a
sale, a customer), not a company.

```
ponds                  pond_types            fish_species
fish_stockings         fish_mortalities      harvests
feed_types             feed_purchases        feed_usages
feed_stock_adjustments growth_records        inspections
inspection_schedules   customers             sales
sale_items             customer_payments     suppliers
purchases              purchase_items        supplier_payments
parties                party_transactions    pond_ledger_entries
income_entries         expense_categories    expense_entries
notifications          settings
```

> **Do not blindly create every table.** Before adding one, check whether an
> existing table already covers the concept. If it does, extend it with a new
> migration rather than creating a duplicate.

## 5. Entity documentation (planned business tables)

Each entry: purpose · important columns · relationships · business rules.

### pond_types — ✅ implemented (Phase 2)
- **Purpose:** classification (nursery, grow-out, brood, …).
- **Columns:** `id`, `name` (varchar 100, **unique**), `description`
  (varchar 500, nullable), `is_active` (bool, default true, indexed), `timestamps`.
- **Relationships:** hasMany `ponds`.
- **Rules:** a type **still referenced by ponds cannot be deleted** — the FK uses
  `restrictOnDelete`, and `PondTypeService::delete()` turns the constraint into a
  clear application error. Mark a type inactive to stop it being offered for new
  ponds without removing it.

### ponds — ✅ implemented (Phase 2)
- **Purpose:** the primary operating unit.
- **Columns:** `id`, `pond_number` (varchar 50, **unique**), `name` (varchar 150),
  `pond_type_id` (FK → `pond_types`, `restrictOnDelete`, required), `size`
  (decimal 12,3), `size_unit` (varchar 20), `depth` (decimal 10,3, nullable),
  `depth_unit` (varchar 20, nullable), `location` (varchar 255, nullable),
  `water_source` (varchar 100, nullable), `status` (varchar 30, default `active`,
  indexed), `description` (text, nullable), `is_active` (bool, indexed),
  `timestamps`.
- **Indexes:** unique on `pond_number`; `status`; `is_active`; composite
  `(pond_type_id, status)` for the common "status within a type" filter.
- **Relationships:** belongsTo `pond_types` (as `type()`); hasMany
  `fish_stockings`, `fish_mortalities`, `harvests` (Phase 3), `feed_usages`
  (Phase 4), `ledger_entries` (Phase 5) — as `ledgerEntries()`. `inspections`
  and `growth_records` will be added when those tables exist.
- **Rules:**
  - `size`/`depth` are `decimal`, never float (they are summed and compared by
    later modules, where float drift would show).
  - `is_active` is **derived from `status`** by `PondService` (`active` and
    `empty` are usable), never accepted from the form.
  - Canonical status keys live in `config/ponds.php`: `active`, `inactive`,
    `maintenance`, `empty`.
  - A pond with live stock will not be deletable once fish-stock tables land —
    the guard belongs in `PondService` (the write path) and `PondPolicy`.

### fish_species — ✅ implemented (Phase 3)
- **Purpose:** catalogue of species raised.
- **Columns:** `id`, `name` (varchar 100, **unique**), `local_name` (varchar 100,
  nullable), `scientific_name` (varchar 150, nullable), `default_price_per_kg`
  (decimal 15,2, nullable), `is_active` (bool, default true, indexed),
  `description` (text, nullable), `timestamps`.
- **Relationships:** hasMany `fish_stockings`, `harvests`.
- **Rules:** a species referenced by any stocking or harvest **cannot be deleted**
  (FK `restrictOnDelete` + `FishSpeciesService` guard); mark it inactive instead.

### fish_stockings — ✅ implemented (Phase 3)
- **Purpose:** records fish put into a pond (stock IN).
- **Columns:** `id`, `pond_id` (FK → `ponds`, `restrictOnDelete`),
  `fish_species_id` (FK → `fish_species`, `restrictOnDelete`), `quantity`
  (unsignedInteger), `avg_weight_g` (decimal 10,2, nullable), `total_weight_kg`
  (decimal 12,3, nullable), `unit_cost` / `total_cost` (decimal 15,2, nullable),
  `stocked_on` (date, indexed), `supplier_name` (varchar 150, nullable),
  `note`, `created_by` (FK → `users`, `nullOnDelete`), `timestamps`.
- **Indexes:** `stocked_on`; composite `(pond_id, stocked_on)`.
- **Rules:** increases fish stock. `total_weight_kg` is DERIVED
  (`quantity × avg_weight_g / 1000`) by `FishStockService`, never entered twice.
  Deleting a stocking is refused when it would take the pond negative.
  `supplier_name` is a plain column — there is no `suppliers` table yet.

### fish_mortalities — ✅ implemented (Phase 3)
- **Purpose:** records fish deaths (stock OUT).
- **Columns:** `id`, `pond_id` (FK → `ponds`, `restrictOnDelete`), `quantity`
  (unsignedInteger), `avg_weight_g` (decimal 10,2, nullable), `recorded_on`
  (date, indexed), `cause` (varchar 100, nullable), `note`, `created_by`
  (FK → `users`, `nullOnDelete`), `timestamps`.
- **Indexes:** `recorded_on`; composite `(pond_id, recorded_on)`.
- **Rules:** decreases fish stock; **can never take stock below zero** — the
  service rejects it before writing. `cause` values come from `config/fish.php`.
  Mortality is recorded per pond (not per species).

### harvests — ✅ implemented (Phase 3)
- **Purpose:** records fish removed from a pond (stock OUT).
- **Columns:** `id`, `pond_id` (FK → `ponds`, `restrictOnDelete`),
  `fish_species_id` (FK → `fish_species`, `restrictOnDelete`), `quantity`
  (unsignedInteger), `total_weight_kg` (decimal 12,3, nullable), `avg_weight_g`
  (decimal 10,2, nullable), `harvested_on` (date, indexed), `destination`
  (varchar 150, nullable), `note`, `created_by` (FK → `users`, `nullOnDelete`),
  `timestamps`.
- **Indexes:** `harvested_on`; composite `(pond_id, harvested_on)`.
- **Rules:** decreases fish stock; **can never take stock below zero**.
  `avg_weight_g` is DERIVED from `total_weight_kg` and the count. Feeds sale
  availability — that link belongs to the Sales module and is not created yet.

### feed_types — ✅ implemented (Phase 4)
- **Purpose:** feed product catalogue.
- **Columns:** `id`, `name` (varchar 120, **unique**), `brand` (varchar 120,
  nullable), `protein_percent` (decimal 5,2, nullable), `unit` (varchar 30,
  nullable), `package_weight_kg` (decimal 10,3, nullable), `default_unit_cost`
  (decimal 15,2, nullable), `low_stock_level_kg` (decimal 12,3, nullable),
  `is_active` (bool, default true, indexed), `description` (text, nullable),
  `timestamps`.
- **Relationships:** hasMany `feed_purchases`, `feed_usages`, `feed_adjustments`.
- **Rules:** stock is NOT a column — it is always derived by `FeedStockService`
  from the movement records. A type referenced by any movement **cannot be
  deleted** (FK `restrictOnDelete` + `FeedTypeService` guard); mark it inactive
  instead. `low_stock_level_kg` drives the low-feed-stock signal (`isLow`).

### feed_purchases — ✅ implemented (Phase 4)
- **Purpose:** feed bought from a supplier (feed stock IN).
- **Columns:** `id`, `feed_type_id` (FK → `feed_types`, `restrictOnDelete`),
  `quantity_kg` (decimal 12,3), `unit_cost` (decimal 15,2, nullable),
  `total_cost` (decimal 15,2, nullable — DERIVED), `purchased_on` (date,
  indexed), `invoice_no` (varchar 100, nullable), `supplier_name` (varchar 150,
  nullable), `paid_amount` (decimal 15,2, nullable), `note`, `created_by`
  (FK → `users`, `nullOnDelete`), `timestamps`.
- **Indexes:** `purchased_on`; composite `(feed_type_id, purchased_on)`.
- **Rules:** increases feed stock. `total_cost` is DERIVED
  (`quantity_kg × unit_cost`) by the service, never entered twice. Deleting a
  purchase is refused when it would take stock negative. There is **no
  `supplier_id`** yet — the supplier is a plain `supplier_name` string until the
  Suppliers module adds a nullable FK alongside it.

### feed_usages — ✅ implemented (Phase 4)
- **Purpose:** feed given to a pond (feed stock OUT).
- **Columns:** `id`, `pond_id` (FK → `ponds`, `restrictOnDelete`),
  `feed_type_id` (FK → `feed_types`, `restrictOnDelete`), `quantity_kg`
  (decimal 12,3), `used_on` (date, indexed), `note`, `created_by`
  (FK → `users`, `nullOnDelete`), `timestamps`.
- **Indexes:** `used_on`; composite `(pond_id, used_on)`; `(feed_type_id, used_on)`.
- **Rules:** decreases feed stock; **can never take stock below zero** — the
  service rejects it before writing. This is the feed input to FCR
  (docs/BUSINESS_LOGIC.md §1).

### feed_stock_adjustments — ✅ implemented (Phase 4)
- **Purpose:** manual correction of feed stock with a reason.
- **Columns:** `id`, `feed_type_id` (FK → `feed_types`, `restrictOnDelete`),
  `direction` (varchar 10, indexed — `in` | `out`), `quantity_kg` (decimal 12,3,
  always a positive magnitude), `reason` (varchar 60 — key from
  `config/feed.php`), `note`, `adjusted_on` (date, indexed), `created_by`
  (FK → `users`, `nullOnDelete`), `timestamps`.
- **Indexes:** `direction`; `adjusted_on`; composite `(feed_type_id, adjusted_on)`.
- **Rules:** stock is never changed silently — a **direction and a reason are
  required**. An `out` adjustment can never take stock below zero. Deleting an
  `in` adjustment is refused when the stock it added is already gone.

### growth_records
- **Purpose:** sampled average weight over time, per pond.
- **Columns:** `id`, `pond_id` (FK), `sampled_on`, `avg_weight_g`, `sample_size`,
  `note`, `created_by` (FK users), `timestamps`.
- **Rules:** supplies the "current weight" for FCR and growth charts.

### inspections
- **Purpose:** a pond inspection and its findings.
- **Columns:** `id`, `pond_id` (FK), `inspected_on`, `inspected_by`,
  `water_ph`, `water_temp_c`, `dissolved_oxygen`, `ammonia`, `turbidity`,
  `health_status`, `action_taken`, `note`, `timestamps`.
- **Rules:** drives inspection-due notifications and FCR diagnostics.

### inspection_schedules
- **Purpose:** recurring inspection plan per pond.
- **Columns:** `id`, `pond_id` (FK), `frequency_days`, `next_due_on`,
  `last_completed_on`, `is_active`, `timestamps`.
- **Rules:** `next_due_on` drives inspection-due and overdue notifications.

### customers
- **Purpose:** buyers of fish.
- **Columns:** `id`, `name`, `phone`, `email`, `address`, `opening_balance`,
  `credit_limit`, `is_active`, `timestamps`.
- **Relationships:** hasMany `sales`, `customer_payments`.
- **Rules:** due = sales − payments (see `docs/BUSINESS_LOGIC.md`).

### sales
- **Purpose:** a fish sale to a customer.
- **Columns:** `id`, `customer_id` (FK), `invoice_no` (unique), `sale_date`,
  `subtotal`, `discount`, `total`, `paid_amount`, `due_amount`, `status`,
  `note`, `created_by` (FK users), `timestamps`.
- **Rules:** creates sale items, stock movement, customer balance effect and a
  ledger entry — all inside one transaction.

### sale_items
- **Purpose:** line items of a sale.
- **Columns:** `id`, `sale_id` (FK, cascade), `fish_species_id` (FK, nullable),
  `pond_id` (FK, nullable), `quantity`, `weight_kg`, `unit_price`, `line_total`.

### customer_payments
- **Purpose:** money received from a customer.
- **Columns:** `id`, `customer_id` (FK), `sale_id` (FK, nullable), `amount`,
  `method`, `paid_on`, `reference`, `note`, `created_by` (FK users), `timestamps`.
- **Rules:** reduces the customer due.

### suppliers
- **Purpose:** vendors of feed, fingerlings and supplies.
- **Columns:** `id`, `name`, `phone`, `email`, `address`, `opening_balance`,
  `is_active`, `timestamps`.

### purchases
- **Purpose:** a purchase from a supplier (header).
- **Columns:** `id`, `supplier_id` (FK), `invoice_no`, `purchase_date`,
  `subtotal`, `discount`, `total`, `paid_amount`, `due_amount`, `status`,
  `note`, `created_by` (FK users), `timestamps`.

### purchase_items
- **Purpose:** purchased line items (feed, fingerlings, equipment, other).
- **Columns:** `id`, `purchase_id` (FK, cascade), `item_type`,
  `feed_type_id` / `fish_species_id` (FK, nullable), `description`, `quantity`,
  `unit_cost`, `line_total`.

### supplier_payments
- **Purpose:** money paid to a supplier.
- **Columns:** `id`, `supplier_id` (FK), `purchase_id` (FK, nullable), `amount`,
  `method`, `paid_on`, `reference`, `note`, `created_by` (FK users), `timestamps`.
- **Rules:** reduces the supplier due.

### parties
- **Purpose:** generic ledger counterparties not covered by customer/supplier.
- **Columns:** `id`, `name`, `type`, `phone`, `address`, `opening_balance`,
  `is_active`, `timestamps`.

### party_transactions
- **Purpose:** debit/credit entries against a party.
- **Columns:** `id`, `party_id` (FK), `entry_type` (debit/credit), `amount`,
  `entry_date`, `reference`, `description`, `created_by` (FK users), `timestamps`.
- **Rules:** balance = debits − credits (sign convention in BUSINESS_LOGIC.md).

### pond_ledger_entries — ✅ implemented (Phase 5)
- **Purpose:** money in/out attributed to a specific pond — the basis of pond
  profitability.
- **Columns:** `id`, `pond_id` (FK → `ponds`, `restrictOnDelete`), `entry_type`
  (varchar 10, indexed — `debit` | `credit`), `category` (varchar 60, indexed —
  key from `config/ledger.php`), `amount` (decimal 15,2), `entry_date` (date,
  indexed), `reference` (varchar 100, nullable), `source_type` (varchar 40,
  default `manual`, indexed), `source_id` (unsignedBigInteger, nullable, indexed),
  `description`, `created_by` (FK → `users`, `nullOnDelete`), `timestamps`.
- **Indexes:** `entry_type`, `category`, `entry_date`, `source_type`, `source_id`;
  composite `(pond_id, entry_date)` and `(entry_type, entry_date)`.
- **Rules:** entries are created by the service that owns the underlying
  transaction — never written ad-hoc by a controller. The one write path is
  `PondLedgerService::record()`.
  - **Sign convention:** `debit` = money out; `credit` = money in;
    `Pond Profit = Σ credits − Σ debits`, applied via `Finance/LedgerRules`.
    A negative profit is a real loss and is never clamped.
  - `source_type`/`source_id` are a polymorphic-style pair (string + id), **not**
    an FK: sources live in different tables and `manual` has no row.
  - Deleting a source transaction reverses its entry in the same transaction
    (`PondLedgerService::reverseSource()`).
  - Only entries with `source_type = manual` may be deleted from the UI; a
    generated entry is reversed with its source.

### income_entries
- **Purpose:** income not tied to a sale (misc. farm income).
- **Columns:** `id`, `pond_id` (FK, nullable), `category`, `amount`,
  `entry_date`, `reference`, `note`, `created_by` (FK users), `timestamps`.

### expense_categories
- **Purpose:** classification of expenses.
- **Columns:** `id`, `name`, `is_active`, `timestamps`.

### expense_entries
- **Purpose:** recorded expenses (feed, labour, medicine, electricity, …).
- **Columns:** `id`, `pond_id` (FK, nullable), `expense_category_id` (FK),
  `amount`, `entry_date`, `reference`, `paid_to`, `note`,
  `created_by` (FK users), `timestamps`.

### notifications
- **Purpose:** database-driven alerts (low feed stock, inspection due/overdue,
  customer due, supplier due, payment reminders, system events).
- **Columns:** `id`, `user_id` (FK, nullable = broadcast to all users), `type`,
  `title`, `message`, `data` (json), `notifiable_type`, `notifiable_id`,
  `read_at`, `created_at`.
- **Rules:** generated by services/scheduled commands, not by views.

### settings
- **Purpose:** application configuration (invoice prefix, low-stock defaults,
  inspection defaults, report options).
- **Columns:** `id`, `group`, `key` (unique), `value`, `type`, `timestamps`.
- **Rules:** reads are cached; writes invalidate the cache. Company branding
  (name, logo, currency, timezone) lives on `companies`, **not** here.

## 6. Access control without a tenant column

Because there is one company, isolation is **not** achieved by a company/tent
filter. What actually protects data:

1. **Authentication** — every business route is behind `auth`.
2. **Authorization** — policies/permissions decide whether the user may perform
   the action (see `docs/PERMISSIONS.md`).
3. **Route model binding** — records are resolved by ID and checked by a policy.

Hiding a menu item is **still not** authorization. A user must not gain access by
changing an ID in a URL — the policy check is what prevents that, not a company
column.

> **Do not add a `company_id` to business tables to "be safe".** With one
> company it filters nothing and is pure overhead. The `users.company_id` column
> exists because a user record needs to name its company; business records do not.

## 7. Indexing and performance

- Index every foreign key.
- Index `users.company_id` and `users.email`.
- Unique indexes: `companies.code`, `users.email`, `roles.name`,
  `permissions.name`, `(user_id, role_id)`, `(permission_id, role_id)`,
  `pond_types.name`, `ponds.pond_number`, `fish_species.name`, `feed_types.name`.
- Index columns used for date-range reporting (`stocked_on`, `recorded_on`,
  `harvested_on`, `purchased_on`, `used_on`, `adjusted_on`, `entry_date`).
- Composite indexes for common report filters, e.g. `(status, sale_date)`.
- Eager-load relationships that views iterate to avoid N+1.
- Paginate all list and report views.

## 8. Conventions

- Table names: plural snake_case.
- Foreign keys: `<singular>_id`.
- Money: `decimal(15,2)`. Weights: kg `decimal(12,3)`, grams `decimal(10,2)`.
- Dates: `date` for business dates, `timestamps` for audit.
- Soft deletes only where a record must remain auditable.
- **No `farm_id` / `company_id` on business tables** (see §1 and §6).