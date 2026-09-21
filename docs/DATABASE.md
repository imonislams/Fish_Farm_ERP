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

**No business/ERP tables exist yet.** No destructive command was used — the
`users` change is an incremental `ALTER TABLE`, not a rebuild.

### Seeded content
| Seeder                | Creates                                              | Rows |
| --------------------- | ---------------------------------------------------- | ---- |
| `PermissionSeeder`    | the 70-key permission catalogue                      | 70   |
| `RoleSeeder`          | the 7 system roles + their permission grants         | 7    |
| `AdminUserSeeder`     | the single company and the first Super Admin         | 1 + 1|

All three are **idempotent** (`updateOrCreate` + `sync`). No business data is
seeded — the ERP database stays empty until real records are created.

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

## 4. Target business entities (NOT created yet)

The full intended ERP model. This is the **plan**, not the current schema. Build
it incrementally, one module at a time, keeping `docs/MODULES.md` in step.

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

### pond_types
- **Purpose:** classification (nursery, grow-out, brood, …).
- **Columns:** `id`, `name`, `description`, `is_active`, `timestamps`.
- **Relationships:** hasMany `ponds`.

### ponds
- **Purpose:** the primary operating unit.
- **Columns:** `id`, `pond_type_id` (FK), `name`, `code`, `area_decimal`,
  `average_depth_m`, `capacity_fish`, `status`, `started_on`, `timestamps`.
- **Relationships:** belongsTo `pond_types`; hasMany `fish_stockings`,
  `feed_usages`, `inspections`, `growth_records`, `harvests`,
  `pond_ledger_entries`.
- **Rules:** status transitions are explicit; a pond with live stock cannot be
  deleted (only archived).

### fish_species
- **Purpose:** catalogue of species raised.
- **Columns:** `id`, `name`, `local_name`, `scientific_name`,
  `default_price_per_kg`, `is_active`, `timestamps`.
- **Relationships:** hasMany `fish_stockings`, `sale_items`.

### fish_stockings
- **Purpose:** records fish put into a pond (stock IN).
- **Columns:** `id`, `pond_id` (FK), `fish_species_id` (FK), `quantity`,
  `avg_weight_g`, `total_weight_kg`, `unit_cost`, `total_cost`, `stocked_on`,
  `supplier_id` (FK, nullable), `note`, `created_by` (FK users), `timestamps`.
- **Rules:** increases fish stock. Multi-record writes are transactional.

### fish_mortalities
- **Purpose:** records fish deaths (stock OUT).
- **Columns:** `id`, `pond_id` (FK), `quantity`, `avg_weight_g`, `recorded_on`,
  `cause`, `note`, `created_by` (FK users), `timestamps`.
- **Rules:** decreases fish stock; can never take stock below zero.

### harvests
- **Purpose:** records fish removed from a pond (stock OUT).
- **Columns:** `id`, `pond_id` (FK), `fish_species_id` (FK), `quantity`,
  `total_weight_kg`, `avg_weight_g`, `harvested_on`, `destination`, `note`,
  `created_by` (FK users), `timestamps`.
- **Rules:** decreases fish stock; feeds sale availability.

### feed_types
- **Purpose:** feed product catalogue.
- **Columns:** `id`, `name`, `brand`, `protein_percent`, `unit`,
  `package_weight_kg`, `default_unit_cost`, `low_stock_level_kg`, `is_active`,
  `timestamps`.
- **Relationships:** hasMany `feed_purchases`, `feed_usages`.

### feed_purchases
- **Purpose:** feed bought from a supplier (feed stock IN).
- **Columns:** `id`, `supplier_id` (FK), `feed_type_id` (FK), `quantity_kg`,
  `unit_cost`, `total_cost`, `purchased_on`, `invoice_no`, `paid_amount`,
  `note`, `created_by` (FK users), `timestamps`.
- **Rules:** increases feed stock; creates a supplier due; transactional.

### feed_usages
- **Purpose:** feed given to a pond (feed stock OUT).
- **Columns:** `id`, `pond_id` (FK), `feed_type_id` (FK), `quantity_kg`,
  `used_on`, `note`, `created_by` (FK users), `timestamps`.
- **Rules:** decreases feed stock; can never take stock below zero; drives FCR.

### feed_stock_adjustments
- **Purpose:** manual correction of feed stock with a reason.
- **Columns:** `id`, `feed_type_id` (FK), `direction` (in/out), `quantity_kg`,
  `reason`, `adjusted_on`, `created_by` (FK users), `timestamps`.
- **Rules:** stock is never changed silently — an adjustment always records why.

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

### pond_ledger_entries
- **Purpose:** money in/out attributed to a specific pond — the basis of pond
  profitability.
- **Columns:** `id`, `pond_id` (FK), `entry_type` (debit/credit), `category`,
  `amount`, `entry_date`, `reference`, `source_type`, `source_id`,
  `description`, `created_by` (FK users), `timestamps`.
- **Rules:** entries are created by the service that owns the underlying
  transaction — never written ad-hoc by a controller.

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
  `permissions.name`, `(user_id, role_id)`, `(permission_id, role_id)`.
- Index columns used for date-range reporting (`sale_date`, `purchased_on`,
  `used_on`, `inspected_on`, `entry_date`).
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