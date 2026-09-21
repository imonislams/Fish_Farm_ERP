# Development Workflow — Fish Farm ERP

**Every developer and AI agent must follow this workflow.**
You are a continuing developer on an existing codebase — you do not own it.

---

## BEFORE WORK

### 1. Read the documentation

Read these in order (skip none; they are short by design):

1. `docs/PROJECT.md` — what the system is, stack, rules, restrictions
2. `docs/ARCHITECTURE.md` — what belongs where
3. `docs/MODULES.md` — **what actually exists vs what is pending**
4. `docs/DATABASE.md` — current schema and the target entity list
5. `docs/BUSINESS_LOGIC.md` — authoritative formulas (FCR, stock, finance)
6. `docs/UI_GUIDELINES.md` — design tokens and the component library
7. `docs/PERMISSIONS.md` — roles, permission keys, isolation rules
8. `docs/PWA.md` — offline behaviour and its limits
9. `docs/ROUTES.md` — route layout and naming
10. Either `docs/DEVELOPMENT_WORKFLOW.md` (this file) or `docs/CHANGELOG.md`

Then read the **existing module documentation** for whatever you are about to
touch.

### 2. Inspect the actual code

Documentation can lag. Before changing anything, verify against the source:

- The routes involved (`routes/web.php`)
- The controller(s)
- The model(s) and their relationships
- The migrations and the **actual current schema**
- The Blade views and which components they use
- The CSS tokens and JS behaviour
- `composer.json` / `package.json` if you are considering a dependency

**Never trust old documentation over the actual code.** If they disagree,
understand why the code differs, decide which is right, and update whichever is
wrong.

### 3. Plan the smallest correct change

- Can an existing component, service, table or route be reused? Reuse it.
- Does another module already do something similar? Follow its pattern.
- What is the minimum set of files that must change?

---

## DURING WORK

1. **Reuse existing components.** Check the table in `docs/UI_GUIDELINES.md` §6
   before writing markup.
2. **Reuse existing services.** If a calculation exists, call it. Never copy it.
3. **Do not duplicate business logic.** One calculation, one place.
4. **Do not create duplicate tables.** Check `docs/DATABASE.md` §2/§3 first.
5. **Do not break existing modules.** Preserve working functionality; do not
   rebuild what already works.
6. **Keep UI consistent.** Use design tokens. No ad-hoc colours or gradients.
7. **Keep authorization enforced.** Route middleware + policy. Hiding a menu
   item is not authorization. Version 1 is single-company — do not add tenant
   scoping.
8. **Keep user/farm data isolated.** Never trust an ID from the URL.
9. **Keep database calculations accurate.** Guard every division; use
   `DB::transaction()` for multi-record writes.
10. **No new dependencies** without a recorded justification in CHANGELOG.md.
11. **No destructive database commands.** No `migrate:fresh`, `migrate:refresh`
    or `db:wipe` unless the user explicitly asks.
12. **No fake data.** An unimplemented area renders an honest pending state.
13. **No tests unless asked.** Do not add or run test suites by default.
14. **No Git commits or pushes.** The user controls Git.

### Authorization rules (Phase 1 — enforced)
- **Every protected route needs `permission:` middleware.** Adding a route
  without it is a security bug, not a style issue.
- Use route middleware + a FormRequest `authorize()` for page/action checks.
  Add a policy when the module owns records that need per-record checks.
- `@permission(...)` / `@role(...)` in Blade is **display only**. Never rely on
  it for access control.
- Never write a guard only in the UI. Business guards belong in the service
  (e.g. `UserService` refuses self-deletion and refuses removing the last admin).
- Never trust `user_id`, `role_id`, `company_id` or `permission_id` from input —
  validate with `Rule::exists` and resolve targets via route model binding.
- The single company is resolved through `App\Support\CompanyContext`, never
  from the request.

### While working in this environment
Shell is **PowerShell**, and XAMPP's PHP is not on `PATH`:

```powershell
# Run artisan (`php` and `git` are NOT on PATH on this machine)
Set-Location "C:\xampp\htdocs\Fish-Farm_ERP"
& "C:\xampp\php\php.exe" artisan <command>

# Start a dev server. Use multiple workers: the single-threaded PHP server is
# extremely slow (30s+ per page) and will make browser checks time out.
$env:PHP_CLI_SERVER_WORKERS="8"
Start-Process -FilePath "C:\xampp\php\php.exe" `
  -ArgumentList "-S","127.0.0.1:8000","-t","public" `
  -WorkingDirectory "C:\xampp\htdocs\Fish-Farm_ERP" -WindowStyle Hidden

# Lint a file
& "C:\xampp\php\php.exe" -l app\Services\Fcr\FcrCalculator.php

# Build front-end assets
$env:Path = "C:\Program Files\nodejs;" + $env:Path
& "C:\Program Files\nodejs\npm.cmd" run build
```

Do **not** use `cd /d`, `dir`, `type` or other cmd.exe syntax — the shell is
PowerShell. Use `Set-Location`, `Get-ChildItem`, `Get-Content`.

MySQL/MariaDB must be running and the `fish_farm_erp` database created before
running migrations. See `docs/DATABASE.md` §1.

---

## AFTER WORK

1. **Update the relevant documentation.**
   - New module or status change → `docs/MODULES.md`
   - New/changed calculation → `docs/BUSINESS_LOGIC.md`
   - New/changed table or column → `docs/DATABASE.md`
   - New/changed route → `docs/ROUTES.md`
   - New permission → `docs/PERMISSIONS.md`
   - New UI pattern or token → `docs/UI_GUIDELINES.md`
   - PWA change → `docs/PWA.md`
   - Architecture change → `docs/ARCHITECTURE.md`
2. **Update `docs/CHANGELOG.md`** using the format in that file.
3. **Record architectural changes** — what changed and why.
4. **Record new routes**, tables/columns, permissions and business rules.
5. **Confirm no broken links or routes were introduced.**
   - `php artisan route:list` shows no errors
   - `php artisan view:clear` then load affected pages
   - Sidebar entries resolve (guarded by `Route::has()`)
6. **Verify the build** if any CSS/JS changed: `npm run build`.
7. **Report the change** clearly: what you changed, what you verified, and what
   you could not verify.

---

## Hard rules — never violate

| Rule | Reason |
| --- | --- |
| No `migrate:fresh` / `refresh` / `db:wipe` without explicit user request | destroys data |
| No fake business data, ever | the ERP must be trustworthy |
| No React/Vue/Inertia/Livewire/Bootstrap | stack decision in PROJECT.md |
| No business calculations in Blade | architecture rule |
| No duplicate calculation logic | single source of truth |
| No trusting user-supplied IDs | security |
| No new dependencies without justification | maintainability |
| No tests unless asked | user preference |
| No Git commits/pushes | user controls Git |

---

## Escalation — ask the user when

- A change would require a destructive database operation.
- The requirement conflicts with an existing documented decision.
- A new dependency or frontend technology seems necessary.
- Two modules' ownership of the same data is ambiguous.
- The current implementation contradicts the documentation in a way that needs
  a business decision rather than a technical one.