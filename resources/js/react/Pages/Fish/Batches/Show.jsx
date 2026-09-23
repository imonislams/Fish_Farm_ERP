import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, num, date, usePermission } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../../Components/Card";
import { DataTable } from "../../../Components/DataTable";
import Button from "../../../Components/Button";

/**
 * Batch detail — how the current quantity was reached, from real movements.
 */
function BatchShow({ batch = {}, inputs = {}, movements = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const fmtDate = date;

    const stockingColumns = [
        { key: "date", label: "Date", render: (r) => fmtDate(r.date) },
        {
            key: "reference",
            label: "Reference",
            render: (r) => r.reference || "—",
        },
        {
            key: "quantity",
            label: "Quantity",
            align: "right",
            render: (r) => (
                <span className="font-medium text-success">
                    +{num(r.quantity, 0)}
                </span>
            ),
        },
    ];

    const lossColumns = [
        { key: "date", label: "Date", render: (r) => fmtDate(r.date) },
        {
            key: "reference",
            label: "Reference",
            render: (r) => r.reference || "—",
        },
        {
            key: "quantity",
            label: "Quantity",
            align: "right",
            render: (r) => (
                <span className="font-medium text-danger">
                    −{num(r.quantity, 0)}
                </span>
            ),
        },
    ];

    return (
        <>
            <Head title={batch.code} />

            <PageHeader
                title={batch.code}
                subtitle={`${batch.species || "—"} in ${batch.pond || "—"}`}
                breadcrumb={[
                    { label: "Fish Stock" },
                    { label: "Batches", href: routes["fish.batches.index"] },
                    { label: batch.code },
                ]}
                actions={
                    <>
                        {batch.urls?.pond && (
                            <Button href={batch.urls.pond} variant="outline">
                                View Pond
                            </Button>
                        )}
                        {can("fish.batch.manage") && batch.urls?.edit && (
                            <Button href={batch.urls.edit} variant="secondary">
                                Edit
                            </Button>
                        )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    label="Live fish"
                    value={num(batch.current_quantity, 0)}
                    icon="fish"
                    tone="primary"
                    hint="Stocked − mortality − harvested"
                />
                <KpiCard
                    label="Stocked"
                    value={num(batch.initial_quantity, 0)}
                    icon="plus"
                    tone="success"
                    hint="Opening quantity for the cycle"
                />
                <KpiCard
                    label="Survival"
                    value={
                        batch.survival === null
                            ? "—"
                            : `${num(batch.survival, 1)}%`
                    }
                    icon="chart"
                    tone={
                        batch.survival === null
                            ? "default"
                            : batch.survival >= 90
                              ? "success"
                              : "warning"
                    }
                    hint="Live ÷ stocked × 100"
                />
                <KpiCard
                    label="Status"
                    value={batch.status}
                    icon="book"
                    tone="info"
                    hint={
                        batch.ended_on
                            ? `Ended ${batch.ended_on}`
                            : "Open cycle"
                    }
                />
            </div>

            <div className="mt-5">
                <Card title="How this quantity was reached">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <p className="text-xs uppercase tracking-wide text-muted">
                                Stocked in
                            </p>
                            <p className="mt-1 text-lg font-semibold text-success">
                                +{num(inputs.stocked, 0)}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-muted">
                                Mortality
                            </p>
                            <p className="mt-1 text-lg font-semibold text-danger">
                                −{num(inputs.mortality, 0)}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs uppercase tracking-wide text-muted">
                                Harvested
                            </p>
                            <p className="mt-1 text-lg font-semibold text-warning">
                                −{num(inputs.harvested, 0)}
                            </p>
                        </div>
                    </div>
                    <p className="mt-4 text-xs text-muted">
                        {inputs.transferred_note}
                    </p>
                    <p className="mt-1 text-sm font-semibold text-text">
                        = {num(batch.current_quantity, 0)} live fish
                    </p>
                </Card>
            </div>

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Card
                    padded={false}
                    title="Stockings"
                    actions={
                        <Badge tone="success">
                            {movements.stockings?.length || 0}
                        </Badge>
                    }
                >
                    <DataTable
                        columns={stockingColumns}
                        rows={movements.stockings || []}
                        empty={
                            <div className="px-6 py-8 text-center text-sm text-muted">
                                No stocking records tagged to this batch.
                            </div>
                        }
                    />
                </Card>

                <Card
                    padded={false}
                    title="Mortality & harvest"
                    actions={
                        <Badge tone="danger">
                            {(movements.mortalities?.length || 0) +
                                (movements.harvests?.length || 0)}
                        </Badge>
                    }
                >
                    <DataTable
                        columns={lossColumns}
                        rows={[
                            ...(movements.mortalities || []),
                            ...(movements.harvests || []).map((h) => ({
                                ...h,
                                reference: "Harvest",
                            })),
                        ]}
                        empty={
                            <div className="px-6 py-8 text-center text-sm text-muted">
                                No mortality or harvest records tagged to this
                                batch.
                            </div>
                        }
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(BatchShow, "Fish Batch");
