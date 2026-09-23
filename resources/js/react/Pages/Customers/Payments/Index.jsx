import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money, date, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, DatePicker, Select } from "../../../Components/Form";
import { ExportButton } from "../../../Components/ReportFilters";

/** Customer Payments — money received from customers. */
function CustomerPaymentsIndex({ payments, options = {}, filters = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [values, setValues] = React.useState({
        customer: filters.customer ?? "",
        from: filters.from ?? "",
        to: filters.to ?? "",
    });
    const [busy, setBusy] = React.useState(false);

    const set = (k, v) => setValues((s) => ({ ...s, [k]: v }));

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["customers.payments.index"] || window.location.pathname,
            values,
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["customers.payments.index"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = Object.values(values).some((v) => v !== "" && v != null);

    const exportParams = new URLSearchParams(
        Object.entries(values).filter(([, v]) => v !== "" && v != null),
    ).toString();
    const exportHref = routes["customers.payments.export"]
        ? exportParams
            ? `${routes["customers.payments.export"]}?${exportParams}`
            : routes["customers.payments.export"]
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
            key: "customer",
            label: "Customer",
            render: (r) => (
                <span className="font-medium text-text">{r.customer}</span>
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
        { key: "method", label: "Method" },
        {
            key: "amount",
            label: "Amount",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap font-medium text-success">
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
                can("customer.payment.create") &&
                r.urls?.destroy && (
                    <Button
                        variant="danger"
                        size="sm"
                        onClick={async () => {
                            if (
                                !(await confirm({ title: "Confirm", description: "Delete this payment? The linked invoice's due will be recalculated." }))
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
            <Head title="Customer Payments" />

            <PageHeader
                title="Customer Payments"
                subtitle="Money received from customers. A payment reduces the customer's due."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Customer Payments" },
                ]}
                actions={
                    <>
                        {routes["customers.dues"] && (
                            <Button
                                href={routes["customers.dues"]}
                                variant="outline"
                                icon="report"
                            >
                                Customer Due
                            </Button>
                        )}
                        {exportHref && <ExportButton href={exportHref} />}
                        {can("customer.payment.create") &&
                            routes["customers.payments.create"] && (
                                <Button
                                    href={routes["customers.payments.create"]}
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
                                    customer payment.
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

export default withLayout(CustomerPaymentsIndex, "Customer Payments");
