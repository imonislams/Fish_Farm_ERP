import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money, date, usePermission } from "../../Components/Page";
import { useConfirm } from "../../Components/ConfirmModal";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import { DataTable } from "../../Components/DataTable";
import Button from "../../Components/Button";

/** Sale details — items, payments and totals. */
function SalesShow({ sale }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const itemColumns = [
        {
            key: "label",
            label: "Item",
            render: (r) => (
                <span className="font-medium text-text">{r.label}</span>
            ),
        },
        {
            key: "pond",
            label: "Pond",
            render: (r) => (
                <span className="text-text-soft">{r.pond || "—"}</span>
            ),
        },
        {
            key: "weight",
            label: "Weight",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {r.weight || "—"}
                </span>
            ),
        },
        {
            key: "quantity",
            label: "Qty",
            align: "right",
            render: (r) => (
                <span className="text-text-soft">{r.quantity ?? "—"}</span>
            ),
        },
        {
            key: "unit_price",
            label: "Unit price",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {money(r.unit_price)}
                </span>
            ),
        },
        {
            key: "line_total",
            label: "Line total",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap font-medium text-text">
                    {money(r.line_total)}
                </span>
            ),
        },
    ];

    const paymentColumns = [
        {
            key: "date",
            label: "Date",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {date(r.date)}
                </span>
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
    ];

    return (
        <>
            <Head title={`Sale ${sale.invoice_no}`} />

            <PageHeader
                title={`Sale ${sale.invoice_no}`}
                subtitle={sale.customer || "Customer"}
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Fish Sales", href: routes["sales.list"] },
                    { label: sale.invoice_no },
                ]}
                actions={
                    <>
                        {can("sales.update") && (
                            <Button
                                href={routes["sales.edit"]?.replace(
                                    "{sale}",
                                    sale.id,
                                )}
                                variant="secondary"
                                icon="cog"
                            >
                                Edit sale
                            </Button>
                        )}
                        {routes["sales.list"] && (
                            <Button
                                href={routes["sales.list"]}
                                variant="outline"
                            >
                                All Sales
                            </Button>
                        )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <Card
                        padded={false}
                        title="Sale items"
                        actions={
                            <Badge tone={sale.status_tone}>{sale.status}</Badge>
                        }
                    >
                        <DataTable
                            columns={itemColumns}
                            rows={sale.items}
                            empty={
                                <div className="px-6 py-12 text-center">
                                    <h4 className="text-sm font-semibold text-text">
                                        No line items
                                    </h4>
                                    <p className="mt-1 text-sm text-muted">
                                        This sale has no items recorded.
                                    </p>
                                </div>
                            }
                        />
                    </Card>

                    {sale.payments.length > 0 && (
                        <div className="mt-4">
                            <Card
                                padded={false}
                                title="Payments against this sale"
                            >
                                <DataTable
                                    columns={paymentColumns}
                                    rows={sale.payments}
                                />
                            </Card>
                        </div>
                    )}
                </div>

                <div className="space-y-4">
                    <Card title="Summary">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Customer</dt>
                                <dd className="font-medium text-text">
                                    {sale.customer || "—"}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Date</dt>
                                <dd className="font-medium text-text">
                                    {date(sale.sale_date)}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Subtotal</dt>
                                <dd className="font-medium text-text">
                                    {money(sale.subtotal)}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Discount</dt>
                                <dd className="font-medium text-text">
                                    {money(sale.discount)}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 border-t border-border pt-2">
                                <dt className="font-semibold text-text">
                                    Total
                                </dt>
                                <dd className="font-bold text-text">
                                    {money(sale.total)}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Paid</dt>
                                <dd className="font-medium text-success">
                                    {money(sale.paid)}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Due</dt>
                                <dd
                                    className={`font-medium ${
                                        sale.due > 0
                                            ? "text-danger"
                                            : "text-muted"
                                    }`}
                                >
                                    {money(sale.due)}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Status</dt>
                                <dd>
                                    <Badge tone={sale.status_tone}>
                                        {sale.status}
                                    </Badge>
                                </dd>
                            </div>
                        </dl>
                    </Card>

                    {sale.note && (
                        <Card title="Note">
                            <p className="whitespace-pre-line text-sm text-text-soft">
                                {sale.note}
                            </p>
                        </Card>
                    )}

                    {can("customer.payment.create") && sale.due > 0 && (
                        <Card title="Collect payment">
                            <p className="text-sm text-muted">
                                This sale still has{" "}
                                <strong>{money(sale.due)}</strong> outstanding.
                            </p>
                            {routes["customers.payments.create"] && (
                                <div className="mt-3">
                                    <Button
                                        href={`${routes["customers.payments.create"]}?customer=${sale.customer_id}`}
                                        variant="primary"
                                        size="sm"
                                    >
                                        Record payment
                                    </Button>
                                </div>
                            )}
                        </Card>
                    )}

                    {can("sales.delete") && (
                        <Card title="Danger zone" tone="danger">
                            <p className="text-sm text-muted">
                                Deleting a sale removes its items and reverses
                                the pond ledger entries it created.
                            </p>
                            <div className="mt-3">
                                <Button
                                    variant="danger"
                                    size="sm"
                                    onClick={async () => {
                                        if (
                                            !(await confirm({ title: "Confirm", description: `Delete sale ${sale.invoice_no}? This cannot be undone.` }))
                                        )
                                            return;
                                        router.delete(
                                            routes["sales.destroy"]?.replace(
                                                "{sale}",
                                                sale.id,
                                            ),
                                            { preserveScroll: true },
                                        );
                                    }}
                                >
                                    Delete sale
                                </Button>
                            </div>
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}

export default withLayout(SalesShow, "Sale");
