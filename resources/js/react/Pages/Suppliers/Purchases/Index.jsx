import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import {
    withLayout,
    money,
    date,
    usePermission,
} from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, DatePicker, Select } from "../../../Components/Form";
import { ExportButton } from "../../../Components/ReportFilters";

/** Supplier Purchases list. */
function PurchasesIndex({ purchases, options = {}, filters = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [values, setValues] = React.useState({
        supplier: filters.supplier ?? "",
        status: filters.status ?? "",
        from: filters.from ?? "",
        to: filters.to ?? "",
    });
    const [busy, setBusy] = React.useState(false);

    const set = (k, v) => setValues((s) => ({ ...s, [k]: v }));

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["suppliers.purchases.index"] || window.location.pathname,
            values,
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["suppliers.purchases.index"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = Object.values(values).some((v) => v !== "" && v != null);

    const exportParams = new URLSearchParams(
        Object.entries(values).filter(([, v]) => v !== "" && v != null),
    ).toString();
    const exportHref = routes["suppliers.purchases.export"]
        ? exportParams
            ? `${routes["suppliers.purchases.export"]}?${exportParams}`
            : routes["suppliers.purchases.export"]
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
            render: (r) =>
                r.invoice_no ? (
                    <code className="rounded bg-surface-muted px-1.5 py-0.5 text-xs">
                        {r.invoice_no}
                    </code>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
        {
            key: "supplier",
            label: "Supplier",
            render: (r) => <span className="text-text-soft">{r.supplier}</span>,
        },
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
                    {can("supplier.payment.create") &&
                        r.due > 0 &&
                        r.supplier_id &&
                        routes["suppliers.payments.create"] && (
                            <Button
                                href={`${routes["suppliers.payments.create"]}?supplier=${r.supplier_id}`}
                                variant="outline"
                                size="sm"
                            >
                                Pay
                            </Button>
                        )}
                    {can("supplier.purchase.create") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="ghost" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("supplier.purchase.create") && r.urls?.destroy && (
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={async () => {
                                if (
                                    !(await confirm({ title: "Confirm", description: `Delete purchase ${r.invoice_no ?? ""}? Its line items will also be removed.` }))
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
            <Head title="Supplier Purchases" />

            <PageHeader
                title="Supplier Purchases"
                subtitle="Purchases from suppliers. Totals and status are derived from each purchase's lines and payments."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Supplier Purchases" },
                ]}
                actions={
                    <>
                        {routes["suppliers.dues"] && (
                            <Button
                                href={routes["suppliers.dues"]}
                                variant="outline"
                                icon="report"
                            >
                                Supplier Due
                            </Button>
                        )}
                        {exportHref && <ExportButton href={exportHref} />}
                        {can("supplier.purchase.create") &&
                            routes["suppliers.purchases.create"] && (
                                <Button
                                    href={routes["suppliers.purchases.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    New Purchase
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5">
                <Card title="Filters">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <Field label="Supplier" name="supplier">
                            <Select
                                name="supplier"
                                value={values.supplier}
                                onChange={(e) =>
                                    set("supplier", e.target.value)
                                }
                                placeholder="All suppliers"
                                options={options.supplierOptions || {}}
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
                    title="Purchase records"
                    actions={<Badge tone="info">{purchases.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={purchases.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No purchases recorded
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or record the first
                                    supplier purchase.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={purchases} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(PurchasesIndex, "Supplier Purchases");
