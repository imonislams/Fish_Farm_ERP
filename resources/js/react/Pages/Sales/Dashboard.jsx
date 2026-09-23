import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, money, date, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../Components/Card";
import { DataTable } from "../../Components/DataTable";
import Button from "../../Components/Button";

/** Sales Dashboard — real financial overview + recent sales. */
function SalesDashboard({ overview, recent = [] }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

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
            key: "due",
            label: "Due",
            align: "right",
            render: (r) => (
                <Badge tone={r.due > 0 ? "warning" : "success"}>
                    {money(r.due)}
                </Badge>
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
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Sales Dashboard" />

            <PageHeader
                title="Sales"
                subtitle="Fish sales to customers. Every figure is a real total from recorded sales."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Sales Dashboard" },
                ]}
                actions={
                    <>
                        {routes["sales.list"] && (
                            <Button
                                href={routes["sales.list"]}
                                variant="outline"
                                icon="cart"
                            >
                                All Sales
                            </Button>
                        )}
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

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    label="Total sales"
                    value={money(overview.sales)}
                    icon="cart"
                    tone="primary"
                    hint="All recorded sales"
                />
                <KpiCard
                    label="Other income"
                    value={money(overview.misc_income)}
                    icon="plus"
                    tone="success"
                    hint="Non-sale income"
                />
                <KpiCard
                    label="Total expense"
                    value={money(overview.expense)}
                    icon="alert"
                    tone="danger"
                    hint="All recorded expenses"
                />
                <KpiCard
                    label="Net profit"
                    value={money(overview.profit)}
                    icon="chart"
                    tone={overview.profit < 0 ? "danger" : "success"}
                    hint={overview.profit < 0 ? "Loss" : "Income − expense"}
                />
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Recent sales"
                    actions={
                        routes["sales.list"] && (
                            <Button
                                href={routes["sales.list"]}
                                variant="ghost"
                                size="sm"
                            >
                                View all
                            </Button>
                        )
                    }
                >
                    <DataTable
                        columns={columns}
                        rows={recent}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No sales recorded yet
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Record a sale to see it here.
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(SalesDashboard, "Sales Dashboard");
