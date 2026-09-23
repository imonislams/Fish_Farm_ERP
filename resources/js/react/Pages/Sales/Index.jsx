import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money, date, usePermission } from "../../Components/Page";
import { useConfirm } from "../../Components/ConfirmModal";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import { DataTable, Pagination } from "../../Components/DataTable";
import Button from "../../Components/Button";
import { Field, Input, DatePicker, Select } from "../../Components/Form";
import { ExportButton } from "../../Components/ReportFilters";

/** Fish Sales list — every recorded sale, filtered and paginated. */
function SalesList({ sales, options = {}, filters = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [values, setValues] = React.useState({
        search: filters.search ?? "",
        customer: filters.customer ?? "",
        status: filters.status ?? "",
        from: filters.from ?? "",
        to: filters.to ?? "",
    });
    const [busy, setBusy] = React.useState(false);

    const set = (k, v) => setValues((s) => ({ ...s, [k]: v }));

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(routes["sales.list"] || window.location.pathname, values, {
            preserveState: true,
            onFinish: () => setBusy(false),
        });
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["sales.list"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = Object.values(values).some((v) => v !== "" && v != null);

    const exportParams = new URLSearchParams(
        Object.entries(values).filter(([, v]) => v !== "" && v != null),
    ).toString();
    const exportHref = routes["sales.export"]
        ? exportParams
            ? `${routes["sales.export"]}?${exportParams}`
            : routes["sales.export"]
        : "";

    const columns = [
        {
            key: "date",
            label: "Date",
            render: (r) => (
                <span className="whitespace-nowrap">{date(r.date)}</span>
            ),
        },
        {
            key: "invoice_no",
            label: "Invoice",
            render: (r) => (
                <code className="rounded bg-surface-muted px-1.5 py-0.5 text-xs">
                    {r.invoice_no}
                </code>
            ),
        },
        { key: "customer", label: "Customer" },
        {
            key: "total",
            label: "Total",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap font-medium">
                    {money(r.total)}
                </span>
            ),
        },
        {
            key: "paid",
            label: "Paid",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-success">
                    {money(r.paid)}
                </span>
            ),
        },
        {
            key: "due",
            label: "Due",
            align: "right",
            render: (r) => (
                <span
                    className={`whitespace-nowrap ${r.due > 0 ? "text-danger" : "text-muted"}`}
                >
                    {money(r.due)}
                </span>
            ),
        },
        {
            key: "status",
            label: "Status",
            align: "center",
            render: (r) => <Badge tone={r.status_tone}>{r.status}</Badge>,
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    <Button href={r.urls?.show} variant="outline" size="sm">
                        View
                    </Button>
                    {can("sales.update") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="ghost" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("sales.delete") && r.urls?.destroy && (
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={async () => {
                                if (
                                    !(await confirm({ title: "Confirm", description: `Delete sale ${r.invoice_no}? Its items and pond ledger entries will be reversed.` }))
                                )
                                    return;
                                router.delete(r.urls.destroy, {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            Delete
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Fish Sales" />

            <PageHeader
                title="Fish Sales"
                subtitle="Every recorded sale to a customer."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Fish Sales" },
                ]}
                actions={
                    <>
                        {routes["sales.index"] && (
                            <Button
                                href={routes["sales.index"]}
                                variant="outline"
                                icon="chart"
                            >
                                Dashboard
                            </Button>
                        )}
                        {exportHref && <ExportButton href={exportHref} />}
                        {can("sales.create") && routes["sales.create"] && (
                            <Button
                                href={routes["sales.create"]}
                                variant="secondary"
                                icon="plus"
                            >
                                New Sale
                            </Button>
                        )}
                    </>
                }
            />

            <div className="mt-5">
                <Card title="Search &amp; filters">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <Field label="Invoice" name="search">
                            <Input
                                name="search"
                                value={values.search}
                                onChange={(e) => set("search", e.target.value)}
                                placeholder="Invoice number…"
                            />
                        </Field>

                        <Field label="Customer" name="customer">
                            <Select
                                name="customer"
                                value={values.customer}
                                onChange={(e) =>
                                    set("customer", e.target.value)
                                }
                                placeholder="All customers"
                                options={options.customerOptions || {}}
                            />
                        </Field>

                        <Field label="Status" name="status">
                            <Select
                                name="status"
                                value={values.status}
                                onChange={(e) => set("status", e.target.value)}
                                placeholder="All statuses"
                                options={options.statusOptions || {}}
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
                    </form>
                </Card>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Sales"
                    actions={<Badge tone="info">{sales.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={sales.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No sales found
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or record the first
                                    sale.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={sales} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(SalesList, "Fish Sales");
