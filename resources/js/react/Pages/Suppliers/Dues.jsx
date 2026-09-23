import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, money, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../Components/Card";
import { DataTable } from "../../Components/DataTable";
import Button from "../../Components/Button";

const CHIPS = [
    { key: "", label: "All" },
    { key: "due", label: "Payable" },
    { key: "credit", label: "In credit" },
    { key: "settled", label: "Settled" },
];

const TITLES = {
    due: "Suppliers payable",
    credit: "Suppliers in credit",
    settled: "Settled suppliers",
    "": "All suppliers",
};

/** Supplier Due — what the farm owes its suppliers. */
function SuppliersDues({ suppliers = [], summary = {}, only = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const chipHref = (key) => {
        const base = routes["suppliers.dues"] || window.location.pathname;
        return key === "" ? base : `${base}?only=${key}`;
    };

    const columns = [
        {
            key: "name",
            label: "Supplier",
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
            label: "Payable",
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
                    {routes["suppliers.purchases.index"] && (
                        <Button
                            href={`${routes["suppliers.purchases.index"]}?supplier=${r.id}`}
                            variant="outline"
                            size="sm"
                        >
                            Purchases
                        </Button>
                    )}
                    {can("supplier.payment.create") &&
                        routes["suppliers.payments.create"] && (
                            <Button
                                href={`${routes["suppliers.payments.create"]}?supplier=${r.id}`}
                                variant="ghost"
                                size="sm"
                            >
                                Pay
                            </Button>
                        )}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Supplier Due" />

            <PageHeader
                title="Supplier Due"
                subtitle="What the farm still owes its suppliers. Figures are derived from real purchases and payments."
                breadcrumb={[{ label: "Suppliers" }, { label: "Supplier Due" }]}
                actions={
                    <>
                        {routes["suppliers.index"] && (
                            <Button
                                href={routes["suppliers.index"]}
                                variant="outline"
                                icon="truck"
                            >
                                All Suppliers
                            </Button>
                        )}
                        {routes["suppliers.dues.export"] && (
                            <a
                                href={routes["suppliers.dues.export"]}
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
                    label="Total payable"
                    value={money(summary.totalPayable)}
                    icon="truck"
                    tone="warning"
                    hint="Still owed to suppliers"
                />
                <KpiCard
                    label="Total purchases"
                    value={money(summary.totalPurchases)}
                    icon="plus"
                    tone="primary"
                    hint="All recorded purchases"
                />
                <KpiCard
                    label="Total paid"
                    value={money(summary.totalPaid)}
                    icon="download"
                    tone="success"
                    hint="All supplier payments"
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
                    title={TITLES[only] || TITLES[""]}
                    actions={
                        <Badge tone="info">{suppliers.length} shown</Badge>
                    }
                >
                    <DataTable
                        columns={columns}
                        rows={suppliers}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    Nothing to show
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    No supplier matches this filter.
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(SuppliersDues, "Supplier Due");
