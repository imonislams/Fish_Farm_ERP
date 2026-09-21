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

### Fish stock — `app/Services/Fish/FishStockService.php`

| Event            | Direction |
| ---------------- | --------- |
| Fish Stocking    | INCREASE  |
| Mortality        | DECREASE  |
| Harvest          | DECREASE  |
| Adjustment in    | INCREASE  |
| Adjustment out   | DECREASE  |
| Transfer in/out  | INCREASE / DECREASE |

Rules:
1. Every movement records its source record (`source_type` + `source_id`).
2. Stock can never go negative — a movement that would is rejected with a
   `DomainException` **before** anything is written.
3. Validate the movement first (`wouldGoNegative`), then write.
4. Multi-record changes run inside `DB::transaction()`.

### Feed stock — `app/Services/Feed/FeedStockService.php`

| Event             | Direction          |
| ----------------- | ------------------ |
| Feed Purchase     | INCREASE           |
| Feed Usage        | DECREASE           |
| Stock Adjustment  | INCREASE/DECREASE  |
| Wastage           | DECREASE           |

Rules: same four as fish stock, plus:
5. Feed is measured in kg, rounded to 3 decimals.
6. When stock reaches `feed_types.low_stock_level_kg`, raise a low-feed-stock
   notification (`isLow()`).

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

## 4. Pond ledger

`pond_ledger_entries` attributes money to a specific pond, giving per-pond
profitability:

```
Pond Profit = Pond Income (credits) − Pond Expenses (debits)
             + opening balance/stock value adjustments
```

Rules:
1. Entries are written by the service that owns the originating transaction
   (a sale, a feed purchase, an expense). Controllers never write ledger rows.
2. Every entry records `source_type` + `source_id` for traceability.
3. Deleting a source transaction must reverse its ledger entry **in the same
   transaction** — never leave an orphan ledger row.

## 5. Feed purchasing and usage

```
Feed Purchase → feed stock INCREASE  + supplier due INCREASE + expense/asset entry
Feed Usage    → feed stock DECREASE  + pond ledger debit      + FCR input
```

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