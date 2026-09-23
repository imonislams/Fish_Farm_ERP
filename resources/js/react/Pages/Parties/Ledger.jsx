import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, money } from "../../Components/Page";
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

const TITLES = {
    due: "Parties owing",
    credit: "Parties in credit",
    settled: "Settled parties",
    "": "All parties",
};

/** Party Ledger — every party's balance at a glance. */
function PartiesLedger({ parties = [], summary = {}, only = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};

    const chipHref = (key) => {
        const base = routes["parties.ledger"] || window.location.pathname;
        return key === "" ? base : `${base}?only=${key}`;
    };

    const columns = [
        {
            key: "name",
            label: "Party",
            render: (r) => (
                <span className="font-medium text-text">{r.name}</span>
            ),
        },
        { key: "type", label: "Type" },
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
            key: "balance",
            label: "Balance",
            align: "right",
            render: (r) => (
                <Badge
                    tone={
                        r.balance > 0
                            ? "warning"
                            : r.balance < 0
                              ? "info"
                              : "success"
                    }
                >
                    {money(r.balance)}
                </Badge>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {routes["parties.transactions"] && (
                        <Button
                            href={`${routes["parties.transactions"]}?party=${r.id}`}
                            variant="outline"
                            size="sm"
                        >
                            Transactions
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Party Ledger" />

            <PageHeader
                title="Party Ledger"
                subtitle="Every party's balance. Positive means the party owes the farm."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Party Ledger" },
                ]}
                actions={
                    <>
                        {routes["parties.index"] && (
                            <Button
                                href={routes["parties.index"]}
                                variant="outline"
                                icon="users"
                            >
                                All Parties
                            </Button>
                        )}
                        {routes["parties.ledger.export"] && (
                            <a
                                href={routes["parties.ledger.export"]}
                                className="inline-flex items-center justify-center gap-2 rounded-control border border-border-strong bg-surface px-3.5 py-2 text-sm font-medium text-text transition-colors hover:bg-surface-muted"
                            >
                                Export CSV
                            </a>
                        )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <KpiCard
                    label="Total receivable"
                    value={money(summary.totalReceivable)}
                    icon="plus"
                    tone="success"
                    hint="Owed to the farm by parties"
                />
                <KpiCard
                    label="Total payable"
                    value={money(summary.totalPayable)}
                    icon="truck"
                    tone="warning"
                    hint="Owed by the farm to parties"
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
                    actions={<Badge tone="info">{parties.length} shown</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={parties}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    Nothing to show
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    No party matches this filter.
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(PartiesLedger, "Party Ledger");
