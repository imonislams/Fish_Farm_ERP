import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, num } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import { DataTable } from "../../Components/DataTable";
import Button from "../../Components/Button";

/** Pond Comparison — every pond's efficiency side by side. */
function FcrComparison({ ranked = [], unranked = [], pondCount = 0 }) {
    const { props } = usePage();
    const routes = props.routes || {};

    const rankedColumns = [
        {
            key: "rank",
            label: "#",
            align: "center",
            render: (r) => (
                <span className="font-medium text-muted">{r.rank}</span>
            ),
        },
        {
            key: "pond",
            label: "Pond",
            render: (r) => (
                <div>
                    <Button
                        href={r.urls?.show}
                        variant="ghost"
                        size="sm"
                        className="-ml-2.5 !px-2.5 font-medium"
                    >
                        {r.pond}
                    </Button>
                    <p className="mt-0.5 pl-1 text-xs text-muted">
                        <code className="rounded bg-surface-muted px-1.5 py-0.5">
                            {r.pond_number}
                        </code>
                    </p>
                </div>
            ),
        },
        {
            key: "type",
            label: "Type",
            render: (r) => (
                <span className="text-text-soft">{r.type || "—"}</span>
            ),
        },
        {
            key: "live_stock",
            label: "Live stock",
            align: "right",
            render: (r) => (
                <span className="text-text-soft">{num(r.live_stock, 0)}</span>
            ),
        },
        {
            key: "feed_kg",
            label: "Feed used",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">{`${num(r.feed_kg, 3)} kg`}</span>
            ),
        },
        {
            key: "gain_kg",
            label: "Weight gain",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">{`${num(r.gain_kg, 3)} kg`}</span>
            ),
        },
        {
            key: "fcr",
            label: "FCR",
            align: "center",
            render: (r) => <Badge tone={r.band_tone}>{r.fcr_display}</Badge>,
        },
        {
            key: "band",
            label: "Band",
            align: "center",
            render: (r) => (
                <span className="text-text-soft">{r.band_label}</span>
            ),
        },
    ];

    const unrankedColumns = [
        {
            key: "pond",
            label: "Pond",
            render: (r) => (
                <div>
                    <span className="font-medium text-text">{r.pond}</span>
                    <p className="mt-0.5 text-xs text-muted">
                        <code className="rounded bg-surface-muted px-1.5 py-0.5">
                            {r.pond_number}
                        </code>
                    </p>
                </div>
            ),
        },
        {
            key: "live_stock",
            label: "Live stock",
            align: "right",
            render: (r) => (
                <span className="text-text-soft">{num(r.live_stock, 0)}</span>
            ),
        },
        {
            key: "feed_kg",
            label: "Feed used",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">{`${num(r.feed_kg, 3)} kg`}</span>
            ),
        },
        {
            key: "fcr",
            label: "FCR",
            align: "center",
            render: () => <Badge tone="default">—</Badge>,
        },
        {
            key: "reason",
            label: "Reason",
            render: (r) => (
                <span className="text-sm text-muted">{r.reason}</span>
            ),
        },
    ];

    return (
        <>
            <Head title="Pond Comparison" />

            <PageHeader
                title="Pond Comparison"
                subtitle="Every pond's efficiency side by side — FCR, growth and inspection record."
                breadcrumb={[
                    { label: "FCR & Growth" },
                    { label: "Pond Comparison" },
                ]}
                actions={
                    routes["fcr.index"] && (
                        <Button
                            href={routes["fcr.index"]}
                            variant="outline"
                            icon="chart"
                        >
                            FCR Dashboard
                        </Button>
                    )
                }
            />

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Ranked by FCR"
                    actions={
                        <Badge tone="info">
                            {ranked.length} of {pondCount} ponds comparable
                        </Badge>
                    }
                >
                    <DataTable
                        columns={rankedColumns}
                        rows={ranked}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No pond has a computable FCR yet
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    A pond's FCR needs feed usage, a growth
                                    sample, and live stock. Record those to make
                                    ponds comparable.
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>

            {unranked.length > 0 && (
                <div className="mt-5">
                    <Card
                        padded={false}
                        title="Not yet comparable"
                        actions={
                            <Badge tone="warning">
                                {unranked.length} pond(s)
                            </Badge>
                        }
                    >
                        <p className="px-4 pt-4 text-sm text-muted">
                            These ponds do not have a computable FCR yet. They
                            are listed with the real reason — never given a
                            placeholder ratio.
                        </p>
                        <DataTable columns={unrankedColumns} rows={unranked} />
                    </Card>
                </div>
            )}

            <div className="mt-5 surface-card border-info/30 p-4 text-sm text-text-soft">
                Ranking uses the real FCR only. A pond whose ratio cannot be
                computed is{" "}
                <strong className="text-text">excluded from the ranking</strong>{" "}
                and listed separately with its reason — it is never given an
                invented value to make the table look complete.
            </div>
        </>
    );
}

export default withLayout(FcrComparison, "Pond Comparison");
