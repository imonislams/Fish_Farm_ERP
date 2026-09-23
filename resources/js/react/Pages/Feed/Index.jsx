import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, num, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../Components/Card";
import { DataTable } from "../../Components/DataTable";
import Button from "../../Components/Button";

/**
 * Feed dashboard — the feed stock position.
 *
 * Every figure is a real aggregate from FeedStockService / the movement tables.
 * Types at or below their reorder level are surfaced as a distinct low-stock list.
 */
function FeedDashboard({
    totalStockKg = 0,
    totalPurchasedKg = 0,
    totalUsedKg = 0,
    typeCount = 0,
    typesWithStock = [],
    lowStock = [],
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const lowIds = new Set(lowStock.map((t) => t.id));

    const lowColumns = [
        {
            key: "name",
            label: "Feed type",
            render: (r) => (
                <span className="font-medium text-text">{r.name}</span>
            ),
        },
        {
            key: "stock",
            label: "In stock",
            align: "right",
            render: (r) => (
                <Badge tone="warning">{`${num(r.stock, 3)} kg`}</Badge>
            ),
        },
        {
            key: "reorder",
            label: "Reorder level",
            align: "right",
            render: (r) => <span>{`${num(r.reorder, 3)} kg`}</span>,
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: () =>
                can("feed.purchase") && routes["feed.purchases.create"] ? (
                    <Button
                        href={routes["feed.purchases.create"]}
                        variant="outline"
                        size="sm"
                    >
                        Record purchase
                    </Button>
                ) : null,
        },
    ];

    const typeColumns = [
        {
            key: "name",
            label: "Feed type",
            render: (r) => (
                <div>
                    <p className="font-medium text-text">{r.name}</p>
                    {r.brand && (
                        <p className="mt-0.5 text-xs text-muted">{r.brand}</p>
                    )}
                </div>
            ),
        },
        {
            key: "unit",
            label: "Unit",
            render: (r) => <span className="text-muted">{r.unit || "—"}</span>,
        },
        {
            key: "stock",
            label: "In stock",
            align: "right",
            render: (r) => (
                <Badge tone={lowIds.has(r.id) ? "warning" : "primary"}>
                    {`${num(r.stock, 3)} kg`}
                </Badge>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {routes["feed.usages.index"] && (
                        <Button
                            href={`${routes["feed.usages.index"]}?type=${r.id}`}
                            variant="outline"
                            size="sm"
                        >
                            Usages
                        </Button>
                    )}
                    {routes["feed.purchases.index"] && (
                        <Button
                            href={`${routes["feed.purchases.index"]}?type=${r.id}`}
                            variant="ghost"
                            size="sm"
                        >
                            Purchases
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Food Dashboard" />

            <PageHeader
                title="Food Management"
                subtitle="Feed stock position across the farm. Every figure is a real total from recorded movements."
                breadcrumb={[
                    { label: "Food Management" },
                    { label: "Food Dashboard" },
                ]}
                actions={
                    <>
                        {can("feed.purchase") &&
                            routes["feed.purchases.create"] && (
                                <Button
                                    href={routes["feed.purchases.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    Record Purchase
                                </Button>
                            )}
                        {can("feed.usage") && routes["feed.usages.create"] && (
                            <Button
                                href={routes["feed.usages.create"]}
                                variant="outline"
                                icon="feed"
                            >
                                Record Usage
                            </Button>
                        )}
                        {can("feed.adjust") &&
                            routes["feed.adjustments.create"] && (
                                <Button
                                    href={routes["feed.adjustments.create"]}
                                    variant="outline"
                                    icon="book"
                                >
                                    Adjust Stock
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    label="Feed in stock"
                    value={`${num(totalStockKg, 3)} kg`}
                    icon="feed"
                    tone="primary"
                    hint="Purchases + adjustments − usage"
                />
                <KpiCard
                    label="Total purchased"
                    value={`${num(totalPurchasedKg, 3)} kg`}
                    icon="plus"
                    tone="success"
                    hint="All purchase records"
                />
                <KpiCard
                    label="Total used"
                    value={`${num(totalUsedKg, 3)} kg`}
                    icon="truck"
                    tone="info"
                    hint="All usage records"
                />
                <KpiCard
                    label="Feed types"
                    value={num(typeCount, 0)}
                    icon="book"
                    tone={lowStock.length > 0 ? "warning" : "default"}
                    hint={
                        lowStock.length > 0
                            ? `${lowStock.length} running low`
                            : "All above reorder level"
                    }
                />
            </div>

            {lowStock.length > 0 && (
                <div className="mt-5">
                    <Card
                        padded={false}
                        title="Low feed stock"
                        actions={
                            <Badge tone="warning">
                                {lowStock.length} type(s) at or below reorder
                                level
                            </Badge>
                        }
                    >
                        <DataTable columns={lowColumns} rows={lowStock} />
                    </Card>
                </div>
            )}

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Stock by feed type"
                    actions={
                        <Badge tone="info">
                            {typesWithStock.length} of {typeCount} types hold
                            stock
                        </Badge>
                    }
                >
                    <DataTable
                        columns={typeColumns}
                        rows={typesWithStock}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No feed in stock
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    {typeCount === 0
                                        ? "There are no feed types yet. Create a feed type, then record a purchase."
                                        : "No feed type currently holds stock. Record a purchase to begin."}
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>

            <div className="mt-5 surface-card border border-info/30 p-4 text-sm text-text-soft">
                Feed in stock is{" "}
                <strong className="text-text">
                    purchases + adjustments in − usage − adjustments out
                </strong>{" "}
                and is always derived from recorded movements — it is never
                typed in directly. Stock can never go below zero, and an
                adjustment always records its reason.
            </div>
        </>
    );
}

export default withLayout(FeedDashboard, "Food Dashboard");
