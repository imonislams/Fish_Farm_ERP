import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money, date, usePermission } from "../../Components/Page";
import { useConfirm } from "../../Components/ConfirmModal";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../Components/Card";
import { DataTable, Pagination } from "../../Components/DataTable";
import Button from "../../Components/Button";
import { Field, Input, DatePicker, Select } from "../../Components/Form";

/** Pond Transactions — every ledger entry attributed to a pond, filterable. */
function LedgerTransactions({
    entries,
    summary,
    options = {},
    filters = {},
    currency = "",
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [values, setValues] = React.useState({
        pond: filters.pond ?? "",
        entry_type: filters.entry_type ?? "",
        category: filters.category ?? "",
        search: filters.search ?? "",
        from: filters.from ?? "",
        to: filters.to ?? "",
    });
    const [busy, setBusy] = React.useState(false);

    const set = (k, v) => setValues((s) => ({ ...s, [k]: v }));

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["ledger.transactions"] || window.location.pathname,
            values,
            {
                preserveState: true,
                onFinish: () => setBusy(false),
            },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["ledger.transactions"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = Object.values(values).some((v) => v !== "" && v != null);

    const columns = [
        {
            key: "date",
            label: "Date",
            render: (r) => (
                <span className="whitespace-nowrap">{date(r.date)}</span>
            ),
        },
        {
            key: "pond",
            label: "Pond",
            render: (r) => (
                <div>
                    <span className="font-medium text-text">
                        {r.pond || "—"}
                    </span>
                    {r.pond_number && (
                        <p className="mt-0.5 text-xs text-muted">
                            <code className="rounded bg-surface-muted px-1.5 py-0.5">
                                {r.pond_number}
                            </code>
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: "type",
            label: "Type",
            align: "center",
            render: (r) => <Badge tone={r.type_tone}>{r.type_short}</Badge>,
        },
        {
            key: "category",
            label: "Category",
            render: (r) => <span className="text-text-soft">{r.category}</span>,
        },
        {
            key: "reference",
            label: "Reference",
            render: (r) => (
                <span className="text-muted">{r.reference || "—"}</span>
            ),
        },
        {
            key: "source",
            label: "Source",
            align: "center",
            render: (r) =>
                r.source_type === "manual" ? (
                    <Badge tone="default">Manual</Badge>
                ) : (
                    <Badge tone="info">{r.source_label}</Badge>
                ),
        },
        {
            key: "amount",
            label: "Amount",
            align: "right",
            render: (r) => (
                <span
                    className={`whitespace-nowrap font-medium ${
                        r.is_credit ? "text-success" : "text-danger"
                    }`}
                >
                    {money(r.amount, currency)}
                </span>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {r.urls?.pond && (
                        <Button href={r.urls.pond} variant="outline" size="sm">
                            Pond
                        </Button>
                    )}
                    {can("ledger.delete") &&
                        (r.source_type === "manual" && r.urls?.destroy ? (
                            <Button
                                variant="danger"
                                size="sm"
                                onClick={async () => {
                                    if (
                                        !(await confirm({ title: "Confirm", description: "Delete this ledger entry? This cannot be undone." }))
                                    )
                                        return;
                                    router.delete(r.urls.destroy, {
                                        preserveScroll: true,
                                    });
                                }}
                            >
                                Delete
                            </Button>
                        ) : (
                            <Badge
                                tone="default"
                                title="Reversed automatically when its source record is removed"
                            >
                                Auto
                            </Badge>
                        ))}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Pond Transactions" />

            <PageHeader
                title="Pond Transactions"
                subtitle="Every ledger entry attributed to a pond. Totals reflect the current filters."
                breadcrumb={[
                    { label: "Pond Ledger" },
                    { label: "Pond Transactions" },
                ]}
                actions={
                    <>
                        {routes["ledger.index"] && (
                            <Button
                                href={routes["ledger.index"]}
                                variant="outline"
                                icon="chart"
                            >
                                Ledger Dashboard
                            </Button>
                        )}
                        {can("ledger.create") &&
                            routes["ledger.transactions.create"] && (
                                <Button
                                    href={routes["ledger.transactions.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    New Entry
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <KpiCard
                    label="Income (filtered)"
                    value={money(summary.income, currency)}
                    icon="plus"
                    tone="success"
                />
                <KpiCard
                    label="Expense (filtered)"
                    value={money(summary.expense, currency)}
                    icon="truck"
                    tone="danger"
                />
                <KpiCard
                    label="Profit (filtered)"
                    value={money(summary.profit, currency)}
                    icon="chart"
                    tone={summary.profit < 0 ? "danger" : "primary"}
                />
            </div>

            <div className="mt-5">
                <Card title="Search &amp; filters">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <Field label="Pond" name="pond">
                            <Select
                                name="pond"
                                value={values.pond}
                                onChange={(e) => set("pond", e.target.value)}
                                placeholder="All ponds"
                                options={options.pondOptions || {}}
                            />
                        </Field>

                        <Field label="Type" name="entry_type">
                            <Select
                                name="entry_type"
                                value={values.entry_type}
                                onChange={(e) =>
                                    set("entry_type", e.target.value)
                                }
                                placeholder="All types"
                                options={options.typeOptions || {}}
                            />
                        </Field>

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

                        <Field label="Search" name="search">
                            <Input
                                name="search"
                                value={values.search}
                                onChange={(e) => set("search", e.target.value)}
                                placeholder="Reference or description…"
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

                        <div className="flex items-end gap-2 sm:col-span-2">
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
                    title="Ledger entries"
                    actions={<Badge tone="info">{entries.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={entries.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No ledger entries
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or record the first
                                    income or expense for a pond.
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

export default withLayout(LedgerTransactions, "Pond Transactions");
