import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, num, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../Components/Card";
import { DataTable } from "../../Components/DataTable";
import Button from "../../Components/Button";

/**
 * Fish Stock dashboard — the farm's live fish position.
 *
 * Every figure is a real aggregate from FishStockService / the movement tables.
 * Only ponds that actually hold fish are listed; the rest are summarised.
 */
function FishStockDashboard({
    liveStock = 0,
    totalStocked = 0,
    totalMortalities = 0,
    totalHarvested = 0,
    pondCount = 0,
    pondsWithStock = [],
    stockByPond = {},
    species = [],
    stockBySpecies = {},
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const pondColumns = [
        {
            key: "name",
            label: "Pond",
            render: (r) => (
                <div>
                    <Button
                        href={r.urls?.show}
                        variant="ghost"
                        size="sm"
                        className="-ml-2.5 !px-2.5 font-medium"
                    >
                        {r.name}
                    </Button>
                    <p className="mt-0.5 pl-1 text-xs text-muted">
                        <code className="rounded bg-surface-muted px-1.5 py-0.5">
                            {r.pond_number}
                        </code>
                    </p>
                </div>
            ),
        },
        { key: "type", label: "Type", render: (r) => r.type || "—" },
        {
            key: "stock",
            label: "Live fish",
            align: "right",
            render: (r) => <Badge tone="primary">{num(r.stock, 0)} fish</Badge>,
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {routes["fish.stockings.index"] && (
                        <Button
                            href={`${routes["fish.stockings.index"]}?pond=${r.id}`}
                            variant="outline"
                            size="sm"
                        >
                            Stockings
                        </Button>
                    )}
                    <Button href={r.urls?.show} variant="ghost" size="sm">
                        Pond
                    </Button>
                </div>
            ),
        },
    ];

    const speciesColumns = [
        {
            key: "name",
            label: "Species",
            render: (r) => (
                <span className="font-medium text-text">{r.name}</span>
            ),
        },
        {
            key: "local_name",
            label: "Local name",
            render: (r) => (
                <span className="text-muted">{r.local_name || "—"}</span>
            ),
        },
        {
            key: "stock",
            label: "Live fish",
            align: "right",
            render: (r) => (
                <Badge tone={r.stock > 0 ? "primary" : "default"}>
                    {num(r.stock, 0)}
                </Badge>
            ),
        },
        {
            key: "is_active",
            label: "Status",
            align: "center",
            render: (r) =>
                r.is_active ? (
                    <Badge tone="success" dot>
                        Active
                    </Badge>
                ) : (
                    <Badge tone="danger" dot>
                        Inactive
                    </Badge>
                ),
        },
    ];

    return (
        <>
            <Head title="Stock Dashboard" />

            <PageHeader
                title="Fish Stock"
                subtitle="The farm's live fish position. Every figure is a real total from recorded movements."
                breadcrumb={[
                    { label: "Fish Stock" },
                    { label: "Stock Dashboard" },
                ]}
                actions={
                    <>
                        {can("fish.stock") &&
                            routes["fish.stockings.create"] && (
                                <Button
                                    href={routes["fish.stockings.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    Record Stocking
                                </Button>
                            )}
                        {can("fish.mortality") &&
                            routes["fish.mortalities.create"] && (
                                <Button
                                    href={routes["fish.mortalities.create"]}
                                    variant="outline"
                                    icon="alert"
                                >
                                    Mortality
                                </Button>
                            )}
                        {can("fish.harvest") &&
                            routes["fish.harvests.create"] && (
                                <Button
                                    href={routes["fish.harvests.create"]}
                                    variant="outline"
                                    icon="truck"
                                >
                                    Harvest
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    label="Live fish"
                    value={num(liveStock, 0)}
                    icon="fish"
                    tone="primary"
                    hint="Stocked − mortality − harvested"
                />
                <KpiCard
                    label="Total stocked"
                    value={num(totalStocked, 0)}
                    icon="plus"
                    tone="success"
                    hint="All stocking records"
                />
                <KpiCard
                    label="Total mortality"
                    value={num(totalMortalities, 0)}
                    icon="alert"
                    tone="warning"
                    hint="All mortality records"
                />
                <KpiCard
                    label="Total harvested"
                    value={num(totalHarvested, 0)}
                    icon="truck"
                    tone="info"
                    hint="All harvest records"
                />
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Live stock by pond"
                    actions={
                        <Badge tone="info">
                            {pondsWithStock.length} of {pondCount} ponds hold
                            fish
                        </Badge>
                    }
                >
                    <DataTable
                        columns={pondColumns}
                        rows={pondsWithStock}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No live fish recorded
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    {pondCount === 0
                                        ? "There are no ponds yet. Create a pond, then record a stocking."
                                        : "No pond currently holds live fish. Record a stocking to begin."}
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Stock by species"
                    actions={
                        can("fish.species.manage") &&
                        routes["fish.species.index"] && (
                            <Button
                                href={routes["fish.species.index"]}
                                variant="ghost"
                                size="sm"
                            >
                                Manage species
                            </Button>
                        )
                    }
                >
                    <DataTable
                        columns={speciesColumns}
                        rows={species}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No species defined
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Add a fish species before recording
                                    stockings or harvests.
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>

            <div className="mt-5 surface-card border border-info/30 p-4 text-sm text-text-soft">
                Live stock is{" "}
                <strong className="text-text">
                    stocked − mortality − harvested
                </strong>{" "}
                and is always derived from recorded movements — it is never
                typed in directly. Mortality is recorded per pond, so the
                per-species figure covers stockings and harvests only.
            </div>
        </>
    );
}

export default withLayout(FishStockDashboard, "Stock Dashboard");
