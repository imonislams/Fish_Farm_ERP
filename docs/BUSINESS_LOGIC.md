# Business Logic — Fish Farm ERP

**This document is the authoritative definition of the system's calculations.**
Implementations must match it exactly. If code and this document disagree,
inspect the code, decide which is correct, and update whichever is wrong.

Business logic lives in `app/Services/**` — never in controllers or Blade.

---

## 1. FCR (Feed Conversion Ratio)

Implemented in `app/Services/Fcr/FcrCalculator.php`
(mirrored by `FcrResult.php`).

### Formula

```
FCR = Total Feed Consumed (kg) / Weight Gain (kg)

Weight Gain (kg) = (Current Avg Weight (g) − Starting Avg Weight (g))
                   × Stocked Fish Count
                   ÷ 1000
```

### Definitions

| Term                  | Meaning                                                      |
| --------------------- | ------------------------------------------------------------ |
| Total Feed Consumed   | Sum of `feed_usages.quantity_kg` for the pond/period          |
| Starting Weight       | Average fish weight at stocking (`fish_stockings.avg_weight_g`) |
| Current Weight        | Latest sampled average weight (`growth_records.avg_weight_g`) |
| Weight Gain           | `(current − starting) × stock count`, converted to kg         |
| FCR                   | Feed consumed ÷ weight gain, to 4 decimal places              |

### Edge cases — never divide by zero

The calculator returns an **unavailable** result (FCR = `null`) with an explicit
state, rather than `0`, `INF` or a crash:

| Condition                | State              | UI shows |
| ------------------------ | ------------------ | -------- |
| No fish stocked          | `no_stock`         | `—`      |
| Weight decreased         | `negative_growth`  | `—`      |
| Weight gain is zero      | `no_growth`        | `—`      |
| No feed consumed         | `no_feed`          | `—`      |
| Valid inputs             | `ok`               | the ratio |

An FCR of `null` **must** render as `—` with the accompanying reason
(`FcrResult::reason()`), never as `0.00`. Zero is a meaningful number and would
be a lie.

### Interpretation bands (presentation only)

`<= 1.2` excellent · `<= 1.6` good · `<= 2.0` fair · `> 2.0` poor.
Bands affect colour/tone only — they never change the calculation.

## 2. Stock logic

Stock is **only** ever changed by recording the underlying transaction. There is
no code path that adjusts a stock figure silently.

### Fish stock — `app/Services/Fish/FishStockService.php` — ✅ implemented (Phase 3)

| Event            | Direction |
| ---------------- | --------- |
| Fish Stocking    | INCREASE  |
| Mortality        | DECREASE  |
| Harvest          | DECREASE  |
| Adjustment in    | INCREASE  |
| Adjustment out   | DECREASE  |
| Transfer in/out  | INCREASE / DECREASE |

Rules (all enforced in the service — the write path):
1. Every movement is a real row (`fish_stockings`, `fish_mortalities`,
   `harvests`); stock is never typed in directly and never changed silently.
2. Stock can never go negative — a movement that would is rejected with a
   `DomainException` **before** anything is written.
3. The movement is validated first (`wouldGoNegative`), then written.
4. Every write runs inside `DB::transaction()`; OUT movements lock the pond row
   (`lockForUpdate`) so two concurrent writes cannot both pass the guard.
5. `currentStock()` is the ONE definition:
   `stocked − mortality − harvested` (clamped at zero defensively).
6. Derived weights are computed once here: `total_weight_kg = quantity ×
   avg_weight_g / 1000` and the inverse for `avg_weight_g`. A missing weight is
   stored and shown as `null` / `—`, never as `0`.
7. Deleting a stocking (an IN movement) is refused when it would take the pond
   negative (the fish it added are already gone). Deleting an OUT movement simply
   restores stock, so it needs no guard.

### Feed stock — `app/Services/Feed/FeedStockService.php` — ✅ implemented (Phase 4)

| Event             | Direction          |
| ----------------- | ------------------ |
| Feed Purchase     | INCREASE           |
| Feed Usage        | DECREASE           |
| Stock Adjustment  | INCREASE/DECREASE  |
| Wastage           | DECREASE           |

Rules: same as fish stock (§2), plus:
5. Feed is measured in **kg**, rounded to 3 decimals.
6. When stock reaches `feed_types.low_stock_level_kg`, the type reads as low
   (`isLow()`) — surfaced on the feed dashboard and the type list.
7. An adjustment REQUIRES a direction and a reason: stock is never changed
   silently. `currentStockKg()` is the ONE definition:
   `purchases + adjustments-in − usage − adjustments-out`.
8. `total_cost` is DERIVED (`quantity_kg × unit_cost`); it is never entered
   twice. A missing unit cost leaves the cost `null`, never `0`.

## 3. Financial logic

Centralized in `app/Services/Finance/`.

### Core equations

```
Customer:   Sale       − Payment  = Due
Supplier:   Purchase   − Payment  = Due
Party:      Debit      − Credit   = Balance
Profit:     Income     − Expense  = Net Profit
```

### Sign convention — `app/Services/Finance/LedgerRules.php`

| Balance   | Meaning                                    |
| --------- | ------------------------------------------ |
| positive  | the counterparty owes the farm (a "due")   |
| negative  | credit in favour of the counterparty       |
| zero      | settled (`isSettled()`)                    |

This convention is defined **once** in `LedgerRules` and must be used
identically by customer dues, supplier dues, party ledgers, the cash-position
metric and all reports. Duplicating the arithmetic elsewhere is a defect.

### Guarded division

Report percentages (margin, target achievement) use
`LedgerRules::percentage()`, which returns `null` for a zero base instead of
dividing by zero.

### Net profit

`LedgerRules::netProfit()` may legitimately return a negative figure — a loss.
Reports must display it as a loss, not clamp it to zero.

## 4. Pond ledger — ✅ implemented (Phase 5)

`pond_ledger_entries` attributes money to a specific pond, giving per-pond
profitability:

```
Pond Profit = Pond Income (credits) − Pond Expenses (debits)
             + opening balance/stock value adjustments
```

Rules (all enforced by `App\Services\Pond\PondLedgerService`):
1. Entries are written by the service that owns the originating transaction
   (a sale, a feed usage, an expense). Controllers never write ledger rows —
   the single write path is `PondLedgerService::record()`.
2. Every entry records `source_type` + `source_id` for traceability.
3. Deleting a source transaction must reverse its ledger entry **in the same
   transaction** (`reverseSource()`) — never leave an orphan ledger row.
4. The sign convention (`LedgerRules`) is not reimplemented: `PondLedgerService`
   delegates to it, so `Pond Profit = credits − debits` is defined once.
5. A **negative profit is a real loss** and is displayed as one — never clamped
   to zero (see §3).
6. Only `source_type = manual` entries may be deleted from the UI; a generated
   entry is reversed with its source, never removed out from under it.
7. `category` must belong to the entry's own `entry_type` — an income category
   on a debit entry would mislabel the pond's costs.

**Manual entries (interim):** the Sales and Finance modules that will normally
write entries do not exist yet, so the ledger accepts hand-recorded entries
(`source_type = manual`). When those modules land they call the same `record()`
with their own source type. This keeps the module genuinely usable today without
inventing figures.

## 5. Feed purchasing and usage

```
Feed Purchase → feed stock INCREASE  + supplier due INCREASE + expense/asset entry
Feed Usage    → feed stock DECREASE  + pond ledger debit      + FCR input
```

## 5a. Fish stock (Phase 3 — implemented)

See §2 for the movement rules. This section covers the module's own semantics.

**Live stock** is always `stocked − mortality − harvested`, derived by
`FishStockService`. It is exposed on the pond (`Pond::currentStock()`,
`Pond::hasLiveStock()`) and on the dashboard, and is never persisted — so it can
never drift from the movements that produced it.

**Per-species stock** (`stockBySpecies()`) counts stockings minus harvests by
species. Mortality is recorded **per pond**, not per species, so it is NOT
subtracted from a species figure. This is stated in the UI so the number is
understood rather than assumed.

**Guards:**
- A mortality or harvest above the pond's live stock is rejected in the
  FormRequest (clean field message) **and** in the service (authoritative).
- A **species in use** by any stocking/harvest cannot be deleted —
  `FishSpeciesService` throws a `DomainException` naming the counts.
- A **pond holding live fish** (or with any movement history) cannot be deleted —
  `PondService::delete()` refuses it with an explanation.

## 5b. Feed management (Phase 4 — implemented)

See §2 for the movement rules. This section covers the module's own semantics.

**Feed stock** is always `purchases + adjustments-in − usage − adjustments-out`,
derived by `FeedStockService::currentStockKg()`. It is never persisted, so it can
never drift from the movements that produced it. It is exposed per feed type and
as a farm-wide total.

**Units:** feed is always kilograms internally (3 decimals), even when the
catalogue stores a display unit such as "kg bag". `feed_types.package_weight_kg`
is reference data for converting bags to kg at entry, not a stored quantity.

**Derived cost:** `total_cost = quantity_kg × unit_cost`, computed once in the
service. A missing unit cost leaves the cost `null` (rendered `—`), never `0`.

**Low stock:** a feed type reads as low when its current stock is at or below
`low_stock_level_kg` (`isLow()`). The threshold is per type; a type with no
threshold is never reported low.

**Guards:**
- A usage or an OUT adjustment above current stock is rejected in the FormRequest
  (clean field message) **and** in the service (authoritative, on a row-locked
  feed type).
- Every adjustment requires a **direction** and a **reason** — stock is never
  changed silently.
- Deleting a purchase or an IN adjustment is refused when it would take stock
  negative (the stock it added is already gone).
- A **feed type in use** by any movement cannot be deleted — `FeedTypeService`
  throws a `DomainException` naming the counts; mark it inactive instead.

## 6. Sales

A sale is the most coupled operation in the system. Creating one must, inside a
single `DB::transaction()`:

1. Create the `sales` header.
2. Create its `sale_items`.
3. Record the fish stock movement (stock OUT).
4. Increase the customer's due.
5. Write the pond ledger entry.
6. Update the sale status.

If any step fails, nothing is written. The database must never be left with a
sale but no stock movement, or a payment with no ledger entry.

## 7. Date conventions

- Business dates are stored as `date` (`sale_date`, `used_on`, `inspected_on`).
- "Today" figures use the farm's local timezone.
- Date-range reports are inclusive of both endpoints.
- Display format: `d M Y` (`App\Support\Dates::DISPLAY`).

## 8. Number conventions

| Quantity     | Storage        | Display        |
| ------------ | -------------- | -------------- |
| Money        | `decimal(15,2)`| 2 decimals, `৳` prefix |
| Weight (kg)  | `decimal(12,3)`| up to 3 decimals |
| Weight (g)   | `decimal(10,2)`| up to 2 decimals |
| Counts       | integer        | no decimals    |
| FCR          | computed       | 2 decimals     |

Use `App\Support\Money::format()` for money so the currency symbol comes from
configuration, not from hard-coded strings in views.

## 9. Rules for implementers

1. One calculation, one place. If a number is needed in three views, compute it
   in one service and pass it to all three.
2. Never compute business figures in Blade.
3. Never let a write touch stock or balances outside a service.
4. Always guard division.
5. Always use transactions for multi-record writes.
6. Never present `null`/undefined as `0`.
## 10. Pond management (Phase 2 — implemented)

The first business module. Its rules are defined once in
`config/ponds.php` (values) and `App\Services\Pond\*` (behaviour) — never
hard-coded in a controller or Blade template.

### Status

Four canonical keys, stored in `ponds.status`, defined in `config/ponds.php`:

| Key           | Label       | Usable (`is_active`) |
| ------------- | ----------- | :------------------: |
| `active`      | Active      | yes                  |
| `empty`       | Empty       | yes                  |
| `maintenance` | Maintenance | no                   |
| `inactive`    | Inactive    | no                   |

- `is_active` is **derived** from `status` by `PondService` (see the "usable"
  flag in the catalogue). It is **never** accepted from the form, so the boolean
  and the status can never contradict each other.
- The status is a plain enumerable string — there is no state-machine
  transition rule yet (all four statuses are freely selectable). Explicit
  transition rules arrive with the fish-stock module, when "a pond with live
  stock" becomes a meaningful state.

### Measurements

- `size` is **required** and must be `> 0`. `depth` is optional.
- Both are `decimal`, never float — they are summed and compared by later
  modules (stocking density, feed per area, reports), where float drift would
  show up.
- A blank depth is stored as `null` ("not recorded"), not `0`, and is displayed
  as an em dash `—`.
- Display strings are produced by `Pond::sizeDisplay()` / `Pond::depthDisplay()`
  so no Blade file performs arithmetic. Trailing zeros are trimmed
  (`12.500` → `12.5`).

### Uniqueness

- `ponds.pond_number` is unique — an operating identifier, not a primary key.
  It stays editable; the update rule ignores the current record.
- `pond_types.name` is unique.

### Pond type deletion

A pond **must** be classified (`pond_type_id` is required, FK-checked). A pond
type still referenced by one or more ponds **cannot be deleted**:

1. the database FK uses `restrictOnDelete`, and
2. `PondTypeService::delete()` checks first and throws a `DomainException`
   naming the pond count.

The controller turns that exception into a flash **error** (a business-rule
refusal), not a 403. To retire a type that is in use, either reassign its ponds
or mark the type `is_active = false` so it is no longer offered for new ponds.

### Honest empty states

The pond details page shows only real database values. Sections for
not-yet-built modules (fish stock, feed usage, growth, inspections, mortality,
harvest, financial performance) render an explicit "no records yet" state and a
`Pending` badge. **No figure is ever invented** (PROJECT.md §12).
