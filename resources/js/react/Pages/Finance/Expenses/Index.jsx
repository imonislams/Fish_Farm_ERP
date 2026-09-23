import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money, date, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, DatePicker, Select } from "../../../Components/Form";
import { ExportButton } from "../../../Components/ReportFilters";

/** Expenses — farm costs. */
function ExpensesIndex({
    entries,
    options = {},
    filters = {},
    overview = {},
    byCategory = {},
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [values, setValues] = React.useState({
        category: filters.category ?? "",
        pond: filters.pond ?? "",
        from: filters.from ?? "",
        to: filters.to ?? "",
    });
    const [busy, setBusy] = React.useState(false);

    const set = (k, v) => setValues((s) => ({ ...s, [k]: v }));

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["finance.expenses.index"] || window.location.pathname,
            values,
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["finance.expenses.index"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = Object.values(values).some((v) => v !== "" && v != null);

    const exportParams = new URLSearchParams(
        Object.entries(values).filter(([, v]) => v !== "" && v != null),
    ).toString();
    const exportHref = routes["finance.expenses.export"]
        ? exportParams
            ? `${routes["finance.expenses.export"]}?${exportParams}`
            : routes["finance.expenses.export"]
        : "";

    const columns = [
        {
            key: "date",
            label: "Date",
            render: (r) => (
                <span className="whitespace-nowrap">{date(r.date)}</span>
            ),
        },
        { key: "category", label: "Category" },
        {
            key: "pond",
            label: "Pond",
            render: (r) => (
                <span className="text-text-soft">{r.pond || "Farm-wide"}</span>
            ),
        },
        {
            key: "amount",
            label: "Amount",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap font-medium text-danger">
                    {money(r.amount)}
                </span>
            ),
        },
        {
            key: "paid_to",
            label: "Paid To",
            render: (r) => (
                <span className="text-text-soft">{r.paid_to || "—"}</span>
            ),
        },
        {
            key: "reference",
            label: "Reference",
            render: (r) => (
                <span className="text-muted">{r.reference || "—"}</span>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) =>
                can("expense.delete") &&
                r.urls?.destroy && (
                    <Button
                        variant="danger"
                        size="sm"
                        onClick={async () => {
                            if (
                                !(await confirm({ title: "Confirm", description: "Delete this expense entry? Any linked pond ledger entry will be reversed." }))
                            )
                                return;
                            router.delete(r.urls.destroy, {
                                preserveScroll: true,
                            });
                        }}
                    >
                        Delete
                    </Button>
                ),
        },
    ];

    return (
        <>
            <Head title="Expenses" />

            <PageHeader
                title="Expenses"
                subtitle="Farm costs. Each entry is a real recorded figure."
                breadcrumb={[{ label: "Finance" }, { label: "Expenses" }]}
                actions={
                    <>
                        {routes["finance.profit-loss"] && (
                            <Button
                                href={routes["finance.profit-loss"]}
                                variant="outline"
                                icon="report"
                            >
                                Profit &amp; Loss
                            </Button>
                        )}
                        {routes["finance.income.index"] && (
                            <Button
                                href={routes["finance.income.index"]}
                                variant="outline"
                                icon="plus"
                            >
                                Income
                            </Button>
                        )}
                        {exportHref && <ExportButton href={exportHref} />}
                        {can("expense.create") &&
                            routes["finance.expenses.create"] && (
                                <Button
                                    href={routes["finance.expenses.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    Record Expense
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <KpiCard
                    label="Total expense"
                    value={money(overview.expense)}
                    icon="alert"
                    tone="danger"
                    hint="All recorded expenses"
                />
                <KpiCard
                    label="Fish sales"
                    value={money(overview.sales)}
                    icon="cart"
                    tone="primary"
                    hint="Recorded in the Sales module"
                />
                <KpiCard
                    label="Net profit"
                    value={money(overview.profit)}
                    icon="chart"
                    tone={overview.profit < 0 ? "danger" : "success"}
                    hint={overview.profit < 0 ? "Loss" : "Income − expense"}
                />
            </div>

            {Object.keys(byCategory || {}).length > 0 && (
                <div className="mt-5">
                    <Card padded={false} title="Expenses by category">
                        <div className="table-shell">
                            <table className="w-full text-sm">
                                <thead className="bg-surface-muted text-xs uppercase text-muted">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium">
                                            Category
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {Object.entries(byCategory).map(
                                        ([key, amount]) => (
                                            <tr key={key}>
                                                <td className="px-4 py-3 text-text-soft">
                                                    {options.categoryOptions?.[
                                                        key
                                                    ] || key}
                                                </td>
                                                <td className="px-4 py-3 text-right whitespace-nowrap font-medium text-text">
                                                    {money(amount)}
                                                </td>
                                            </tr>
                                        ),
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            )}

            <div className="mt-5">
                <Card title="Filters">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <Field label="Category" name="category">
                            <Select
                                name="category"
                                value={values.category}
                                onChange={(e) =>
                                    set("category", e.target.value)
                                }
                                placeholder="All categories"
                                options={options.categoryOptions || {}}
                            />
                        </Field>

                        <Field label="Pond" name="pond">
                            <Select
                                name="pond"
                                value={values.pond}
                                onChange={(e) => set("pond", e.target.value)}
                                placeholder="All ponds"
                                options={options.pondOptions || {}}
                            />
                        </Field>

                        <Field label="From" name="from">
                            <DatePicker
                                name="from"
                                value={values.from}
                                onChange={(e) => set("from", e.target.value)}
                            />
                        </Field>

                        <Field label="To" name="to">
                            <DatePicker
                                name="to"
                                value={values.to}
                                onChange={(e) => set("to", e.target.value)}
                            />
                        </Field>

                        <div className="flex items-end gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={busy}
                            >
                                Apply
                            </Button>
                            {hasFilters && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={clear}
                                    disabled={busy}
                                >
                                    Clear
                                </Button>
                            )}
                        </div>
                    </form>
                </Card>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Expense entries"
                    actions={<Badge tone="info">{entries.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={entries.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No expenses recorded
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or record the first
                                    expense entry.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={entries} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(ExpensesIndex, "Expenses");
