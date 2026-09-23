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

/** Supplier Payments — money paid out to suppliers. */
function SupplierPaymentsIndex({ payments, options = {}, filters = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [values, setValues] = React.useState({
        supplier: filters.supplier ?? "",
        from: filters.from ?? "",
        to: filters.to ?? "",
    });
    const [busy, setBusy] = React.useState(false);

    const set = (k, v) => setValues((s) => ({ ...s, [k]: v }));

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["suppliers.payments.index"] || window.location.pathname,
            values,
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["suppliers.payments.index"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = Object.values(values).some((v) => v !== "" && v != null);

    const exportParams = new URLSearchParams(
        Object.entries(values).filter(([, v]) => v !== "" && v != null),
    ).toString();
    const exportHref = routes["suppliers.payments.export"]
        ? exportParams
            ? `${routes["suppliers.payments.export"]}?${exportParams}`
            : routes["suppliers.payments.export"]
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
            key: "supplier",
            label: "Supplier",
            render: (r) => (
                <span className="font-medium text-text">{r.supplier}</span>
            ),
        },
        {
            key: "invoice",
            label: "Invoice",
            render: (r) =>
                r.invoice ? (
                    <code className="rounded bg-surface-muted px-1.5 py-0.5 text-xs">
                        {r.invoice}
                    </code>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
        {
            key: "method",
            label: "Method",
            render: (r) => <span className="text-text-soft">{r.method}</span>,
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
                can("supplier.payment.create") &&
                r.urls?.destroy && (
                    <Button
                        variant="danger"
                        size="sm"
                        onClick={async () => {
                            if (
                                !(await confirm({ title: "Confirm", description: "Delete this payment? The linked purchase's due will be recalculated." }))
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
            <Head title="Supplier Payments" />

            <PageHeader
                title="Supplier Payments"
                subtitle="Money paid out to suppliers. A payment reduces what the farm owes."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Supplier Payments" },
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
                        {can("supplier.payment.create") &&
                            routes["suppliers.payments.create"] && (
                                <Button
                                    href={routes["suppliers.payments.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    Record Payment
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
                    title="Payment records"
                    actions={<Badge tone="info">{payments.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={payments.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No payments recorded
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or record the first
                                    supplier payment.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={payments} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(SupplierPaymentsIndex, "Supplier Payments");
