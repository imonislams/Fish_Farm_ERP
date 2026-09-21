# Permissions & Authorization — Fish Farm ERP

**Status: IMPLEMENTED for the Phase 1 foundation.** Roles, permissions and the
`permission:` middleware exist and are enforced in the database and the route
table. Business-module permissions (pond, feed, fish, …) are defined and
assignable but their pages are not built yet.

Implementation map:

| Concern              | Where                                                        |
| -------------------- | ------------------------------------------------------------ |
| Permission catalogue | `config/permissions.php`                                     |
| Seeding              | `database/seeders/PermissionSeeder.php`, `RoleSeeder.php`     |
| Enforcement          | `App\Http\Middleware\EnsurePermission` (alias `permission`)   |
| Super Admin bypass   | `Gate::before()` in `AppServiceProvider`                     |
| User helpers         | `User::hasPermission()`, `hasRole()`, `hasAnyRole()`         |
| Display directives   | `@permission(...)`, `@role(...)` in `AppServiceProvider`      |
| Admin UI             | `/settings/roles`, `/settings/permissions`                   |

---

> **Version 1 uses a single-company architecture. Multiple users operate within
> one company/farm. Multi-company / multi-tenant support is intentionally not
> implemented.** There is no tenant boundary, no tenant middleware, no company
> selector and no `company_id` on business tables. See `docs/DATABASE.md` §1.

## 1. Principles
1. **Authorization is not a UI concern.** Hiding a menu item is cosmetic.
   Enforcement happens at route, policy and FormRequest level.
2. **Never trust an ID from the URL.** Every record must be verified to belong to
   the acting user's farm before it is read or written.
3. **Deny by default.** A missing permission means no access.
4. **One decision, one place.** Each "may this user do this?" question is
   answered by exactly one policy method or permission check.

## 2. Enforcement layers
| Layer            | Mechanism                                        | Guards against            |
| ---------------- | ------------------------------------------------ | ------------------------- |
| Route            | `auth`, `permission:xxx` middleware              | unauthenticated/unauthorised access |
| Controller       | `$this->authorize(...)` / `Gate::authorize(...)` | per-action authorization  |
| FormRequest      | `authorize()` + `withValidator()` rules          | privilege escalation      |
| Service          | write-path guards (e.g. cannot delete last admin) | lockout / bad state       |
| Blade            | `@permission(...)` / `@role(...)` for display    | confusing dead-end buttons |

**Verified in Phase 1:** an authenticated Viewer receives **200** on
`/dashboard`, `/settings/company` and `/settings/profile`, and **403** on
`/settings/users`, `/settings/users/create`, `/settings/roles` and
`/settings/permissions`. Guests are redirected to `/login` from every protected
route. A user cannot change their own role or deactivate themselves.

The Blade layer is **presentation only** — it never substitutes for the others.

## 3. Access control (single company)

Version 1 is **single-company**. There is no tenant boundary to enforce, no
`farm_id`/`company_id` column on business tables, and therefore **no**
`BelongsToFarm` trait or tenant global scope. Do not add one.

What still matters is **authorization** — a user must not perform an action they
lack permission for, and must not reach a record they are not entitled to act on.

Required behaviour:

```php
// Route model binding + policy = authorization is checked per record.
public function edit(Pond $pond)
{
    $this->authorize('update', $pond);   // policy checks the user may update ponds
    ...
}
```

And at the route level, permissions gate the whole group:

```php
Route::middleware('permission:pond.view')->group(function () {
    Route::get('/fish-farm/ponds', [PondController::class, 'index'])->name('ponds.index');
});
```

Use `Gate::authorize()` / policies for per-record decisions and
`permission:` middleware for per-action decisions. Hiding a menu item remains
**cosmetic only**.

> **Future major version:** if multi-company support is ever added, this is the
> section that gains a company-scoped global scope and a `company_id` column.
> The migration path is described in `docs/DATABASE.md` §1. Do not implement it
> now.

## 4. Roles (seeded — 7)

All are `is_system = true` and therefore protected from deletion. Defined in
`config/permissions.php` under `roles`.

| Machine name | Label       | Permissions | Intent                                            |
| ------------ | ----------- | ----------- | ------------------------------------------------- |
| `super_admin`| Super Admin | all (70)    | Full control incl. company, users, roles, perms    |
| `farm_admin` | Farm Admin  | 66          | Farm ops + user management; **no** role definition |
| `manager`    | Manager     | 29          | Day-to-day operations, read-only admin views       |
| `accountant` | Accountant  | 28          | Sales, payments, purchases, ledgers, reporting     |
| `farm_staff` | Farm Staff  | 11          | Field data entry (feed usage, mortality, sampling) |
| `sales_staff`| Sales Staff | 10          | Sales and customer collections                     |
| `viewer`     | Viewer      | 14          | Read-only across the farm                          |

`farm_admin` intentionally lacks `roles.*` and `permissions.manage`: defining
roles stays with Super Admin, matching that role's own description.

Creating an additional role is done through the UI at `/settings/roles/create`;
custom roles have `is_system = false` and are deletable.

## 5. Permission keys
Named `resource.action`, grouped by module. These are **stable identifiers** —
renaming one requires a migration and a CHANGELOG entry. The catalogue lives in
`config/permissions.php` (70 keys) and is seeded by `PermissionSeeder`.

### Foundation modules — ENFORCED in Phase 1
| Group       | Keys                                                                    |
| ----------- | ----------------------------------------------------------------------- |
| Dashboard   | `dashboard.view`                                                         |
| Company     | `company.view` `company.update`                                          |
| Users       | `users.view` `users.create` `users.update` `users.delete`                |
| Roles       | `roles.view` `roles.create` `roles.update` `roles.delete`                |
| Permissions | `permissions.view` `permissions.manage`                                  |
| Settings    | `settings.view` `settings.update` `notifications.manage`                 |

### Pond
`pond.view` `pond.create` `pond.update` `pond.delete`
`pond.type.manage`

### Feed
`feed.view` `feed.purchase` `feed.usage` `feed.adjust`
`feed.type.manage`

### Fish stock
`fish.view` `fish.stock` `fish.mortality` `fish.harvest`
`fish.species.manage`

### FCR & Growth
`fcr.view` `fcr.inspection.create` `fcr.inspection.update`
`fcr.schedule.manage` `growth.view` `growth.create`

### Sales
`sales.view` `sales.create` `sales.update` `sales.delete`
`sales.payment.create`

### Customers
`customer.view` `customer.create` `customer.update` `customer.delete`
`customer.payment.create`

### Suppliers
`supplier.view` `supplier.create` `supplier.update` `supplier.delete`
`supplier.purchase.create` `supplier.payment.create`

### Party
`party.view` `party.create` `party.update` `party.delete`
`party.transaction.create`

### Finance
`finance.view` `income.create` `income.update` `income.delete`
`expense.create` `expense.update` `expense.delete`
`expense.category.manage` `ledger.view`

### Reports
`reports.view` `reports.export` `reports.financial`

### Administration
Replaced in Phase 1 by the granular foundation keys listed at the top of this
section (`company.*`, `users.*`, `roles.*`, `permissions.*`, `settings.*`).
There is no longer a catch-all `users.manage` / `roles.manage` / `settings.manage`
key — permissions are per-action so a role can be granted `users.view` without
`users.delete`. `farm.manage` was removed: the business identity is the
**company** (`company.update`).

## 6. Suggested role → permission mapping

| Permission group | Super Admin | Farm Admin | Manager | Accountant | Farm Staff | Sales Staff | Viewer |
| ---------------- | :---------: | :--------: | :-----: | :--------: | :--------: | :---------: | :----: |
| pond.*           | ✓ | ✓ | ✓ | – | view | view | view |
| feed.*           | ✓ | ✓ | ✓ | view | usage | – | view |
| fish.*           | ✓ | ✓ | ✓ | view | stock/mortality | view | view |
| fcr.*            | ✓ | ✓ | ✓ | view | create | – | view |
| sales.*          | ✓ | ✓ | ✓ | ✓ | – | ✓ | view |
| customer.*       | ✓ | ✓ | ✓ | ✓ | – | ✓ | view |
| supplier.*       | ✓ | ✓ | ✓ | ✓ | – | – | view |
| party.*          | ✓ | ✓ | ✓ | ✓ | – | – | view |
| finance.*        | ✓ | ✓ | view | ✓ | – | – | view |
| reports.*        | ✓ | ✓ | view | ✓ | – | view | view |
| company.view     | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| company.update   | ✓ | ✓ | – | – | – | – | – |
| users.view       | ✓ | ✓ | ✓ | – | – | – | – |
| users.* (write)  | ✓ | ✓ | – | – | – | – | – |
| roles.view       | ✓ | ✓ | ✓ | – | – | – | – |
| roles.* (write)  | ✓ | – | – | – | – | – | – |
| permissions.view | ✓ | ✓ | – | – | – | – | – |
| permissions.manage| ✓ | – | – | – | – | – | – |

## 7. Implementation (done in Phase 1)

All steps are **complete**:

1. ✅ Migrations: `roles`, `permissions`, `permission_role`, `role_user`
   (plus `companies` and the `users.company_id` / `is_active` alteration).
2. ✅ Models `Role`, `Permission` with `belongsToMany` on both sides;
   `User::roles()` / `User::company()`.
3. ✅ `User::hasPermission()`, `hasRole()`, `hasAnyRole()`,
   `permissionNames()` — cached per request with `once()`.
4. ✅ `permission` middleware alias in `bootstrap/app.php`:
   ```php
   $middleware->alias(['permission' => \App\Http\Middleware\EnsurePermission::class]);
   ```
   Used as `->middleware('permission:users.create')`. Accepts multiple keys
   (`permission:a,b`), and also rejects **deactivated** users mid-session.
5. ⏳ Policies per model — deferred to the modules that own those models.
   The Phase 1 foundation uses route middleware + FormRequest `authorize()`,
   which is the correct layer for page/action authorization.
6. ✅ Seeders: `PermissionSeeder`, `RoleSeeder` — both idempotent.
7. ✅ Super Admin bypass via `Gate::before()` in `AppServiceProvider` — the only
   bypass in the system. The `@permission` / `@role` display directives live
   alongside it.

## 7a. First administrator (secure setup)

`AdminUserSeeder` creates the company and the first Super Admin.

- Credentials come from `.env`: `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`.
- **No default password is shipped.** If `ADMIN_PASSWORD` is empty the seeder
  generates a random one and prints it once to the console.
- Idempotent: re-running updates the existing account (e.g. to set a known
  password locally) and leaves the company record untouched.
- The seeded Super Admin holds every permission and can reach Company Settings,
  Users, Roles and Permissions.

## 8. Extending
When adding a permission:

1. Add the key to `config/permissions.php` under the right group.
2. Grant it to the appropriate roles in the same file.
3. Run `php artisan db:seed --class=PermissionSeeder` then `--class=RoleSeeder`
   (both idempotent).
4. **Enforce it** on the route with `permission:` middleware, and in any
   FormRequest that writes.
5. Document it in §5 of this file.
6. Record it in `docs/CHANGELOG.md` under **Permissions**.

## 9. Anti-patterns — do not do these
- Relying on `@permission` / `@can` in Blade as the only check.
- Trusting `$request->id` (or `role_id`, `company_id`, `permission_id`) without
  validation and authorization. Role ids are validated with `Rule::exists`.
- `Model::find($id)` on a business record in a controller (use route model
  binding + an authorization check).
- Adding a Super Admin backdoor anywhere other than `Gate::before()`.
- Letting a user change their own role or deactivate themselves (guarded in
  `UpdateUserRequest::withValidator()` and again in `UserService`).
- Deleting the last active Super Admin, or a role still assigned to users
  (guarded in `UserService` / `RoleService`).
- Checking roles by string comparison scattered across the codebase — use the
  `hasPermission()` / `hasRole()` helpers.