import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, num, date, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../Components/Card";
import { DataTable } from "../../Components/DataTable";
import Button from "../../Components/Button";

/** FCR & Growth dashboard — feed conversion and growth across the farm. */
function FcrDashboard({
    rows = [],
    pondCount = 0,
    averageFcr = null,
    averageBand = "unknown",
    averageHint = "",
    computableCount = 0,
    bestPond = null,
    inspectionStatusCounts = {},
    scheduleStatusCounts = {},
    scheduleTotal = 0,
    recentConcerns = [],
    healthStatuses = {},
    fcrBands = {},
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const inspectionTotal = Object.values(inspectionStatusCounts).reduce(
        (a, b) => a + Number(b || 0),
        0,
    );

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
        {
            key: "live_stock",
            label: "Live stock",
            align: "right",
            render: (r) => (
                <span className="text-text-soft">{num(r.live_stock, 0)}</span>
            ),
        },
        {
            key: "feed",
            label: "Feed used",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {`${num(r.total_feed_kg, 3)} kg`}
                </span>
            ),
        },
        {
            key: "gain",
            label: "Weight gain",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {r.weight_gain_kg != null
                        ? `${num(r.weight_gain_kg, 3)} kg`
                        : "—"}
                </span>
            ),
        },
        {
            key: "fcr",
            label: "FCR",
            align: "center",
            render: (r) => (
                <Badge
                    tone={r.fcr_tone || "default"}
                    title={r.fcr_reason || ""}
                >
                    {r.fcr_display || "—"}
                </Badge>
            ),
        },
        {
            key: "inspections",
            label: "Inspections",
            align: "center",
            render: (r) => (
                <span className="text-muted">{r.inspections_count}</span>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {routes["fcr.growth"] && (
                        <Button
                            href={`${routes["fcr.growth"]}?pond=${r.id}`}
                            variant="outline"
                            size="sm"
                        >
                            Growth
                        </Button>
                    )}
                    {routes["fcr.inspections.index"] && (
                        <Button
                            href={`${routes["fcr.inspections.index"]}?pond=${r.id}`}
                            variant="ghost"
                            size="sm"
                        >
                            Inspections
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    const concernColumns = [
        {
            key: "date",
            label: "Date",
            render: (r) => (
                <span className="whitespace-nowrap">{date(r.date)}</span>
            ),
        },
        {
            key: "pond",
            label: "Pond",
            render: (r) => <span className="text-text-soft">{r.pond}</span>,
        },
        {
            key: "status",
            label: "Status",
            align: "center",
            render: (r) => <Badge tone={r.tone}>{r.label}</Badge>,
        },
    ];

    return (
        <>
            <Head title="FCR Dashboard" />

            <PageHeader
                title="FCR &amp; Growth"
                subtitle="Feed conversion and growth across the farm. Figures come straight from the database."
                breadcrumb={[
                    { label: "FCR & Growth" },
                    { label: "FCR Dashboard" },
                ]}
                actions={
                    <>
                        {routes["fcr.reports"] && (
                            <Button
                                href={routes["fcr.reports"]}
                                variant="outline"
                                icon="report"
                            >
                                FCR Reports
                            </Button>
                        )}
                        {can("fcr.inspection.create") &&
                            routes["fcr.inspections.create"] && (
                                <Button
                                    href={routes["fcr.inspections.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    New Inspection
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    label="Average FCR"
                    value={averageFcr === null ? "—" : num(averageFcr, 2)}
                    icon="chart"
                    tone={
                        averageBand === "unknown"
                            ? "warning"
                            : averageBand === "poor"
                              ? "danger"
                              : "primary"
                    }
                    hint={averageHint}
                />
                <KpiCard
                    label="Best pond"
                    value={bestPond?.name ?? "—"}
                    icon="droplet"
                    tone="success"
                    hint={
                        bestPond
                            ? bestPond.pond_number
                            : "No pond has a computable FCR yet"
                    }
                />
                <KpiCard
                    label="Ponds inspected"
                    value={num(inspectionTotal, 0)}
                    icon="search"
                    tone="primary"
                    hint="Total inspection records"
                />
                <KpiCard
                    label="Schedules overdue"
                    value={num(scheduleStatusCounts.overdue ?? 0, 0)}
                    icon="alert"
                    tone={
                        (scheduleStatusCounts.overdue ?? 0) > 0
                            ? "danger"
                            : "success"
                    }
                    hint={`${scheduleTotal} schedule(s) set`}
                />
            </div>

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Card padded={false} title="Inspection outcomes">
                    {inspectionTotal === 0 ? (
                        <div className="px-6 py-12 text-center">
                            <h4 className="text-sm font-semibold text-text">
                                No inspections recorded
                            </h4>
                            <p className="mt-1 text-sm text-muted">
                                Record a pond inspection to see the health
                                breakdown.
                            </p>
                        </div>
                    ) : (
                        <div className="table-shell">
                            <table className="w-full text-sm">
                                <thead className="bg-surface-muted text-xs uppercase text-muted">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium">
                                            Health status
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Inspections
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {Object.entries(healthStatuses).map(
                                        ([key, meta]) => (
                                            <tr key={key}>
                                                <td className="px-4 py-3">
                                                    <Badge tone={meta.tone}>
                                                        {meta.label}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3 text-right font-medium text-text">
                                                    {num(
                                                        inspectionStatusCounts[
                                                            key
                                                        ] ?? 0,
                                                        0,
                                                    )}
                                                </td>
                                            </tr>
                                        ),
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>

                <Card
                    padded={false}
                    title="Recent concerns"
                    actions={
                        routes["fcr.inspections.index"] && (
                            <Button
                                href={routes["fcr.inspections.index"]}
                                variant="ghost"
                                size="sm"
                            >
                                All inspections
                            </Button>
                        )
                    }
                >
                    <DataTable
                        columns={concernColumns}
                        rows={recentConcerns}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No concerns flagged
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    No inspection has reported a warning or
                                    critical health status.
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Pond efficiency"
                    actions={<Badge tone="info">{pondCount} ponds</Badge>}
                >
                    <DataTable
                        columns={pondColumns}
                        rows={rows}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No ponds yet
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Create a pond, stock it and record feed
                                    usage to see its FCR.
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>

            <div className="mt-5 surface-card border-info/30 p-4 text-sm text-text-soft">
                <strong className="text-text">
                    FCR = feed consumed ÷ weight gain.
                </strong>{" "}
                A pond shows <strong>—</strong> when the ratio genuinely cannot
                be computed — hover it to see why. An unavailable FCR is never
                shown as <code>0.00</code>, because zero would be a lie.
            </div>
        </>
    );
}

export default withLayout(FcrDashboard, "FCR Dashboard");
