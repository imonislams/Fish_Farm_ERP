import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, money, num, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import Button from "../../Components/Button";

/** Pond details — basic info, fish stock, financials and FCR, all real figures. */
function PondsShow({ pond, stock, ledger, fcr, growth }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const hasStock =
        stock.stocked > 0 || stock.mortality > 0 || stock.harvested > 0;
    const hasLedger = ledger.income !== 0 || ledger.expense !== 0;
    const hasFcr =
        fcr.available || growth.samples > 0 || stock.inspection_count > 0;

    const field = (label, value) => (
        <div>
            <dt className="text-xs font-medium uppercase tracking-wide text-muted">
                {label}
            </dt>
            <dd className="mt-1 text-sm text-text-soft">{value || "—"}</dd>
        </div>
    );

    const stat = (label, value, tone = "text-text") => (
        <div className="rounded-control border-border p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-muted">
                {label}
            </p>
            <p className={`mt-1 text-xl font-bold ${tone}`}>{value}</p>
        </div>
    );

    return (
        <>
            <Head title={pond.name} />

            <PageHeader
                title={pond.name}
                subtitle={`Pond ${pond.pond_number} · ${pond.type || "Unclassified"}`}
                breadcrumb={[
                    { label: "Pond Management" },
                    { label: "All Ponds", href: routes["ponds.index"] },
                    { label: pond.name },
                ]}
                actions={
                    <>
                        {can("pond.update") && (
                            <Button
                                href={routes["ponds.edit"]?.replace(
                                    "{pond}",
                                    pond.id,
                                )}
                                variant="secondary"
                                icon="cog"
                            >
                                Edit pond
                            </Button>
                        )}
                        {routes["ponds.index"] && (
                            <Button
                                href={routes["ponds.index"]}
                                variant="outline"
                            >
                                All ponds
                            </Button>
                        )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <Card
                        title="Basic information"
                        subtitle="Details recorded for this pond."
                        actions={
                            <Badge tone={pond.status_tone} dot>
                                {pond.status}
                            </Badge>
                        }
                    >
                        <dl className="grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                            {field("Pond number", pond.pond_number)}
                            {field("Name", pond.name)}
                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-muted">
                                    Type
                                </dt>
                                <dd className="mt-1 text-sm text-text-soft">
                                    {pond.type || "—"}
                                    {pond.type_inactive && (
                                        <Badge tone="warning" className="ml-1">
                                            Inactive type
                                        </Badge>
                                    )}
                                </dd>
                            </div>
                            {field("Size", pond.size)}
                            {field("Depth", pond.depth)}
                            {field("Location", pond.location)}
                            {field("Water source", pond.water_source)}
                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-muted">
                                    Status
                                </dt>
                                <dd className="mt-1">
                                    <Badge tone={pond.status_tone} dot>
                                        {pond.status}
                                    </Badge>
                                </dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-xs font-medium uppercase tracking-wide text-muted">
                                    Description
                                </dt>
                                <dd className="mt-1 whitespace-pre-line text-sm text-text-soft">
                                    {pond.description || "—"}
                                </dd>
                            </div>
                        </dl>
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card title="Record">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Status</dt>
                                <dd>
                                    <Badge tone={pond.status_tone} dot>
                                        {pond.status}
                                    </Badge>
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Operational</dt>
                                <dd>
                                    <Badge
                                        tone={
                                            pond.is_active
                                                ? "success"
                                                : "danger"
                                        }
                                    >
                                        {pond.is_active ? "Yes" : "No"}
                                    </Badge>
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Created</dt>
                                <dd className="font-medium text-text">
                                    {pond.created_at || "—"}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Last updated</dt>
                                <dd className="font-medium text-text">
                                    {pond.updated_at || "—"}
                                </dd>
                            </div>
                        </dl>
                    </Card>

                    <div className="surface-card border-info/30 p-4 text-xs text-text-soft">
                        Operational state is derived from the{" "}
                        <strong className="text-text">status</strong>: active
                        and empty ponds are usable; maintenance and inactive
                        ponds are not.
                    </div>
                </div>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Fish stock"
                    actions={
                        <Badge tone="primary">
                            {num(stock.live, 0)} live fish
                        </Badge>
                    }
                >
                    {!hasStock ? (
                        <div className="px-6 py-12 text-center">
                            <h4 className="text-sm font-semibold text-text">
                                No fish stocking records yet
                            </h4>
                            <p className="mt-1 text-sm text-muted">
                                This pond has no recorded fish movements. Record
                                a stocking to begin.
                            </p>
                            {can("fish.stock") &&
                                routes["fish.stockings.create"] && (
                                    <div className="mt-3">
                                        <Button
                                            href={
                                                routes["fish.stockings.create"]
                                            }
                                            variant="primary"
                                            size="sm"
                                        >
                                            Record Stocking
                                        </Button>
                                    </div>
                                )}
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-1 gap-4 p-4 sm:grid-cols-3">
                                {stat("Stocked", num(stock.stocked, 0))}
                                {stat("Mortality", num(stock.mortality, 0))}
                                {stat("Harvested", num(stock.harvested, 0))}
                            </div>
                            <div className="grid grid-cols-1 gap-4 border-t border-border p-4 sm:grid-cols-3">
                                {stat(
                                    "Live fish",
                                    num(stock.live, 0),
                                    "text-primary",
                                )}
                                {stat(
                                    "Est. biomass",
                                    stock.biomass_kg === null
                                        ? "—"
                                        : `${num(stock.biomass_kg, 2)} kg`,
                                )}
                                {stat(
                                    "Survival",
                                    stock.survival === null
                                        ? "—"
                                        : `${num(stock.survival, 1)}%`,
                                    stock.survival === null
                                        ? "text-muted"
                                        : stock.survival >= 90
                                          ? "text-success"
                                          : "text-warning",
                                )}
                            </div>
                            {stock.species && stock.species.length > 0 && (
                                <div className="border-t border-border px-4 py-3">
                                    <p className="text-xs uppercase tracking-wide text-muted">
                                        Species stocked
                                    </p>
                                    <div className="mt-2 flex flex-wrap gap-2">
                                        {stock.species.map((s) => (
                                            <Badge key={s} tone="info">
                                                {s}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            )}
                            <div className="border-t border-border px-4 py-3">
                                <div className="table-actions">
                                    {routes["fish.stockings.index"] && (
                                        <Button
                                            href={`${routes["fish.stockings.index"]}?pond=${pond.id}`}
                                            variant="outline"
                                            size="sm"
                                        >
                                            Stocking records
                                        </Button>
                                    )}
                                    {routes["fish.mortalities.index"] && (
                                        <Button
                                            href={`${routes["fish.mortalities.index"]}?pond=${pond.id}`}
                                            variant="ghost"
                                            size="sm"
                                        >
                                            Mortality
                                        </Button>
                                    )}
                                    {routes["fish.harvests.index"] && (
                                        <Button
                                            href={`${routes["fish.harvests.index"]}?pond=${pond.id}`}
                                            variant="ghost"
                                            size="sm"
                                        >
                                            Harvest
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </>
                    )}
                </Card>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Financial performance"
                    actions={
                        <Badge
                            tone={
                                ledger.profit < 0
                                    ? "danger"
                                    : ledger.profit > 0
                                      ? "success"
                                      : "default"
                            }
                        >
                            {ledger.profit < 0 ? "Loss" : "Profit"}:{" "}
                            {money(ledger.profit)}
                        </Badge>
                    }
                >
                    {!hasLedger ? (
                        <div className="px-6 py-12 text-center">
                            <h4 className="text-sm font-semibold text-text">
                                No pond financial records yet
                            </h4>
                            <p className="mt-1 text-sm text-muted">
                                No income or expense has been recorded against
                                this pond.
                            </p>
                            {can("ledger.create") &&
                                routes["ledger.transactions.create"] && (
                                    <div className="mt-3">
                                        <Button
                                            href={`${routes["ledger.transactions.create"]}?pond=${pond.id}`}
                                            variant="primary"
                                            size="sm"
                                        >
                                            Record Entry
                                        </Button>
                                    </div>
                                )}
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-1 gap-4 p-4 sm:grid-cols-3">
                                {stat(
                                    "Income",
                                    money(ledger.income),
                                    "text-success",
                                )}
                                {stat(
                                    "Expense",
                                    money(ledger.expense),
                                    "text-danger",
                                )}
                                {stat(
                                    "Profit",
                                    money(ledger.profit),
                                    ledger.profit < 0
                                        ? "text-danger"
                                        : "text-text",
                                )}
                            </div>
                            <div className="border-t border-border px-4 py-3">
                                <div className="table-actions">
                                    {routes["ledger.transactions"] && (
                                        <Button
                                            href={`${routes["ledger.transactions"]}?pond=${pond.id}`}
                                            variant="outline"
                                            size="sm"
                                        >
                                            Pond transactions
                                        </Button>
                                    )}
                                    {can("ledger.create") &&
                                        routes[
                                            "ledger.transactions.create"
                                        ] && (
                                            <Button
                                                href={`${routes["ledger.transactions.create"]}?pond=${pond.id}`}
                                                variant="ghost"
                                                size="sm"
                                            >
                                                New entry
                                            </Button>
                                        )}
                                </div>
                            </div>
                        </>
                    )}
                </Card>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="FCR &amp; growth"
                    actions={
                        <Badge tone={fcr.band_tone}>FCR {fcr.display}</Badge>
                    }
                >
                    {!hasFcr ? (
                        <div className="px-6 py-12 text-center">
                            <h4 className="text-sm font-semibold text-text">
                                No growth or inspection records yet
                            </h4>
                            <p className="mt-1 text-sm text-muted">
                                Record feed usage, a growth sample and an
                                inspection to track this pond's efficiency.
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-1 gap-4 p-4 sm:grid-cols-4">
                                {stat(
                                    "Feed consumed",
                                    `${num(fcr.feed_consumed_kg, 3)} kg`,
                                )}
                                {stat(
                                    "Weight gain",
                                    fcr.available
                                        ? `${num(fcr.gain_kg, 3)} kg`
                                        : "—",
                                )}
                                {stat(
                                    "Latest weight",
                                    growth.latest_g === null
                                        ? "—"
                                        : `${num(growth.latest_g, 2)} g`,
                                )}
                                {stat(
                                    "Inspections",
                                    num(stock.inspection_count, 0),
                                )}
                            </div>

                            {!fcr.available && (
                                <div className="border-t border-border px-4 py-3">
                                    <div className="surface-card border-info/30 p-3 text-xs text-text-soft">
                                        FCR is <strong>—</strong> because{" "}
                                        {fcr.reason
                                            ? fcr.reason
                                                  .charAt(0)
                                                  .toLowerCase() +
                                              fcr.reason.slice(1)
                                            : "it cannot be computed"}
                                    </div>
                                </div>
                            )}

                            <div className="border-t border-border px-4 py-3">
                                <div className="table-actions">
                                    {routes["fcr.growth"] && (
                                        <Button
                                            href={`${routes["fcr.growth"]}?pond=${pond.id}`}
                                            variant="outline"
                                            size="sm"
                                        >
                                            Growth samples
                                        </Button>
                                    )}
                                    {routes["fcr.inspections.index"] && (
                                        <Button
                                            href={`${routes["fcr.inspections.index"]}?pond=${pond.id}`}
                                            variant="ghost"
                                            size="sm"
                                        >
                                            Inspections
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </>
                    )}
                </Card>
            </div>
        </>
    );
}

export default withLayout(PondsShow, "Pond");
