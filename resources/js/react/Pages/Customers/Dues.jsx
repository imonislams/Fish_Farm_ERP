import React from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import { withLayout, money, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../Components/Card";
import { DataTable } from "../../Components/DataTable";
import Button from "../../Components/Button";

const CHIPS = [
    { key: "", label: "All" },
    { key: "due", label: "Owing" },
    { key: "credit", label: "In credit" },
    { key: "settled", label: "Settled" },
];

/** Customer Due — what customers still owe, derived from sales and payments. */
function CustomersDues({
    customers = [],
    summary = {},
    only = "",
    labels = {},
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const chipHref = (key) => {
        const base = routes["customers.dues"] || window.location.pathname;
        return key === "" ? base : `${base}?only=${key}`;
    };

    const columns = [
        {
            key: "name",
            label: "Customer",
            render: (r) => (
                <span className="font-medium text-text">{r.name}</span>
            ),
        },
        {
            key: "phone",
            label: "Phone",
            render: (r) => (
                <span className="text-text-soft">{r.phone || "—"}</span>
            ),
        },
        {
            key: "opening",
            label: "Opening",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-muted">
                    {money(r.opening)}
                </span>
            ),
        },
        {
            key: "due",
            label: "Due",
            align: "right",
            render: (r) => (
                <Badge
                    tone={
                        r.due > 0 ? "warning" : r.due < 0 ? "info" : "success"
                    }
                >
                    {money(r.due)}
                </Badge>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {can("sales.view") && routes["sales.list"] && (
                        <Button
                            href={`${routes["sales.list"]}?customer=${r.id}`}
                            variant="outline"
                            size="sm"
                        >
                            Sales
                        </Button>
                    )}
                    {can("customer.payment.create") &&
                        routes["customers.payments.create"] && (
                            <Button
                                href={`${routes["customers.payments.create"]}?customer=${r.id}`}
                                variant="ghost"
                                size="sm"
                            >
                                Collect
                            </Button>
                        )}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Customer Due" />

            <PageHeader
                title="Customer Due"
                subtitle="What customers still owe the farm. Figures are derived from real sales and payments."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Customer Due" },
                ]}
                actions={
                    <>
                        {routes["customers.index"] && (
                            <Button
                                href={routes["customers.index"]}
                                variant="outline"
                                icon="users"
                            >
                                All Customers
                            </Button>
                        )}
                        {routes["customers.dues.export"] && (
                            <a
                                href={routes["customers.dues.export"]}
                                className="inline-flex items-center justify-center gap-2 rounded-control border border-border-strong bg-surface px-3.5 py-2 text-sm font-medium text-text transition-colors hover:bg-surface-muted"
                            >
                                Export CSV
                            </a>
                        )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <KpiCard
                    label="Total receivable"
                    value={money(summary.totalReceivable)}
                    icon="users"
                    tone="warning"
                    hint="Still owed by customers"
                />
                <KpiCard
                    label="Total sales"
                    value={money(summary.totalSales)}
                    icon="cart"
                    tone="primary"
                    hint="All recorded sales"
                />
                <KpiCard
                    label="Total collected"
                    value={money(summary.totalCollected)}
                    icon="download"
                    tone="success"
                    hint="All customer payments"
                />
            </div>

            <div className="mt-5 flex flex-wrap gap-2">
                {CHIPS.map((chip) => (
                    <Button
                        key={chip.key}
                        href={chipHref(chip.key)}
                        variant={only === chip.key ? "primary" : "outline"}
                        size="sm"
                    >
                        {chip.label}
                    </Button>
                ))}
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title={labels.title || "All customers"}
                    actions={
                        <Badge tone="info">{customers.length} shown</Badge>
                    }
                >
                    <DataTable
                        columns={columns}
                        rows={customers}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    Nothing to show
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    No customer matches this filter.
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(CustomersDues, "Customer Due");
