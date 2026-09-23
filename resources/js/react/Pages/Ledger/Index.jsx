import React from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import { withLayout, money, num, date } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../Components/Card";
import { DataTable, Pagination } from "../../Components/DataTable";
import Button from "../../Components/Button";
import { Field, Select } from "../../Components/Form";
import Icon from "../../Components/Icon";

/**
 * Pond Ledger — the complete transaction history of a pond.
 *
 * Mirrors the Pond Status pattern: clickable summary cards (one per transaction
 * type) filter the timeline below. All rows come from PondLedgerTimelineService;
 * the view computes nothing.
 */
function LedgerIndex({
    pondOptions = {},
    selectedPond = null,
    selectedPondId = null,
    timeline = null,
    currentStock = 0,
    typeTotals = {},
    selectedType = "",
    typeOptions = {},
}) {
    const { props } = usePage();
    const routes = props.routes || {};

    const [pond, setPond] = React.useState(selectedPondId ?? "");
    const [busy, setBusy] = React.useState(false);

    const view = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["ledger.index"] || window.location.pathname,
            pond ? { pond } : {},
            { onFinish: () => setBusy(false) },
        );
    };

    const cardHref = (typeKey) => {
        const base = routes["ledger.index"] || window.location.pathname;
        if (selectedType === typeKey) return `${base}?pond=${selectedPond.id}`;
        return `${base}?pond=${selectedPond.id}&type=${typeKey}`;
    };

    const columns = [
        {
            key: "date",
            label: "Date",
            render: (r) => (
                <span className="whitespace-nowrap">{date(r.date)}</span>
            ),
        },
        {
            key: "type",
            label: "Type",
            align: "center",
            render: (r) => <Badge tone={r.type_tone}>{r.type_short}</Badge>,
        },
        {
            key: "description",
            label: "Description",
            render: (r) => (
                <span className="text-text-soft">{r.description}</span>
            ),
        },
        {
            key: "quantity",
            label: "Quantity",
            align: "right",
            render: (r) => (
                <span
                    className={`whitespace-nowrap ${
                        String(r.quantity || "").startsWith("-")
                            ? "text-danger"
                            : "text-success"
                    }`}
                >
                    {r.quantity ?? "—"}
                </span>
            ),
        },
        {
            key: "money",
            label: "Money",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap">{r.money ?? "—"}</span>
            ),
        },
        {
            key: "reference",
            label: "Ref.",
            render: (r) =>
                r.reference ? (
                    <code className="rounded bg-surface-muted px-1.5 py-0.5 text-xs">
                        {r.reference}
                    </code>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
        {
            key: "user",
            label: "User",
            render: (r) => <span className="whitespace-nowrap">{r.user}</span>,
        },
    ];

    return (
        <>
            <Head title="Pond Ledger" />

            <PageHeader
                title="Pond Ledger"
                subtitle="Complete transaction history of a pond. Counts come straight from the database."
                breadcrumb={[
                    { label: "Pond Ledger" },
                    { label: "Pond Ledger" },
                ]}
                actions={
                    <>
                        {selectedPond && (
                            <Badge tone="info">
                                {selectedPond.pond_number}
                            </Badge>
                        )}
                        {routes["ledger.transactions"] && (
                            <Button
                                href={routes["ledger.transactions"]}
                                variant="outline"
                                icon="book"
                            >
                                Pond Transactions
                            </Button>
                        )}
                    </>
                }
            />

            <div className="mt-5">
                <Card title="Pond selection">
                    <form
                        onSubmit={view}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <Field
                            label="Pond"
                            name="pond"
                            hint="Select a pond to view its full history."
                        >
                            <Select
                                name="pond"
                                value={pond}
                                onChange={(e) => setPond(e.target.value)}
                                placeholder="Select a pond…"
                                options={pondOptions}
                            />
                        </Field>
                        <div className="flex items-end gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={busy}
                            >
                                View Ledger
                            </Button>
                            {selectedPondId && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => {
                                        setPond("");
                                        setBusy(true);
                                        router.get(
                                            routes["ledger.index"] ||
                                                window.location.pathname,
                                            {},
                                            { onFinish: () => setBusy(false) },
                                        );
                                    }}
                                    disabled={busy}
                                >
                                    Clear
                                </Button>
                            )}
                        </div>
                    </form>
                </Card>
            </div>

            {!selectedPond ? (
                <div className="mt-5">
                    <Card padded={false}>
                        <div className="px-6 py-12 text-center">
                            <h4 className="text-sm font-semibold text-text">
                                Select a pond
                            </h4>
                            <p className="mt-1 text-sm text-muted">
                                Choose a pond above to see its complete
                                transaction history — stocking, mortality,
                                transfers and more.
                            </p>
                        </div>
                    </Card>
                </div>
            ) : (
                <>
                    <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {Object.entries(typeOptions).map(([typeKey, meta]) => {
                            const isSelected = selectedType === typeKey;
                            const t = typeTotals[typeKey] || {
                                count: 0,
                                money: 0,
                            };
                            return (
                                <Link
                                    key={typeKey}
                                    href={cardHref(typeKey)}
                                    className={`surface-card surface-card--hoverable block border p-4 transition-colors ${
                                        isSelected
                                            ? "border-primary ring-1 ring-primary/30"
                                            : ""
                                    }`}
                                    aria-current={
                                        isSelected ? "true" : undefined
                                    }
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-surface-muted text-muted">
                                            <Icon
                                                name={meta.icon || "book"}
                                                className="h-5 w-5"
                                            />
                                        </span>
                                        <Badge tone={meta.tone || "default"}>
                                            {meta.short || typeKey}
                                        </Badge>
                                    </div>

                                    <p className="mt-3 text-2xl font-bold text-text">
                                        {num(t.count, 0)}
                                    </p>
                                    <p className="mt-0.5 text-xs text-muted">
                                        {meta.label || typeKey}
                                    </p>

                                    {t.money > 0 ? (
                                        <p className="mt-2 text-xs font-medium text-success">
                                            {meta.money_display ?? money(t.money)}
                                        </p>
                                    ) : (
                                        <p className="mt-2 text-xs font-medium text-muted">
                                            {t.count === 0
                                                ? "No records"
                                                : "No money attached"}
                                        </p>
                                    )}
                                </Link>
                            );
                        })}
                    </div>

                    <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <KpiCard
                            label="Pond"
                            value={selectedPond.name}
                            icon="droplet"
                            hint={selectedPond.pond_number}
                            tone="primary"
                        />
                        <KpiCard
                            label="Current stock"
                            value={`${num(currentStock, 0)} fish`}
                            icon="fish"
                            tone="default"
                        />
                        <KpiCard
                            label="Transactions"
                            value={num(timeline?.total ?? 0, 0)}
                            icon="book"
                            tone="primary"
                            hint={
                                selectedType !== ""
                                    ? "Filtered"
                                    : "All movements"
                            }
                        />
                    </div>

                    <div className="mt-5">
                        <Card
                            padded={false}
                            title={
                                selectedType !== ""
                                    ? `${typeOptions[selectedType]?.label} records`
                                    : "Transaction history"
                            }
                            subtitle={`${selectedPond.pond_number} — ${selectedPond.name}, newest first.`}
                            actions={
                                <>
                                    <Badge tone="info">
                                        {timeline?.total ?? 0} total
                                    </Badge>
                                    {selectedType !== "" && (
                                        <Button
                                            href={`${routes["ledger.index"]}?pond=${selectedPond.id}`}
                                            variant="ghost"
                                            size="sm"
                                        >
                                            Clear filter
                                        </Button>
                                    )}
                                </>
                            }
                        >
                            <DataTable
                                columns={columns}
                                rows={timeline?.data ?? []}
                                empty={
                                    <div className="px-6 py-12 text-center">
                                        <h4 className="text-sm font-semibold text-text">
                                            {selectedType !== ""
                                                ? "No records of this type"
                                                : "No transactions yet"}
                                        </h4>
                                        <p className="mt-1 text-sm text-muted">
                                            {selectedType !== ""
                                                ? `This pond has no “${
                                                      typeOptions[selectedType]
                                                          ?.label
                                                  }” records. Clear the filter to see the full history.`
                                                : "This pond has no recorded movements. Stock it, record a death or transfer fish in to build its ledger."}
                                        </p>
                                    </div>
                                }
                            />
                            {timeline && <Pagination paginator={timeline} />}
                        </Card>
                    </div>
                </>
            )}
        </>
    );
}

export default withLayout(LedgerIndex, "Pond Ledger");
