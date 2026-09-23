import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, num } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../Components/Card";
import { DataTable } from "../../Components/DataTable";
import Button from "../../Components/Button";
import { Field, DatePicker, Select } from "../../Components/Form";

/** FCR Reports — a date-ranged FCR and growth report. */
function FcrReports({
    rows = [],
    summary = {},
    filters = {},
    pondOptions = {},
    healthStatuses = {},
    inspectionStatusCounts = {},
}) {
    const { props } = usePage();
    const routes = props.routes || {};

    const [pond, setPond] = React.useState(filters.pond ?? "");
    const [from, setFrom] = React.useState(filters.from ?? "");
    const [to, setTo] = React.useState(filters.to ?? "");
    const [busy, setBusy] = React.useState(false);

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["fcr.reports"] || window.location.pathname,
            { pond, from, to },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["fcr.reports"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = pond !== "" || from !== "" || to !== "";

    const columns = [
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
            key: "stock",
            label: "Live stock",
            align: "right",
            render: (r) => (
                <span className="text-text-soft">{num(r.stock_count, 0)}</span>
            ),
        },
        {
            key: "feed",
            label: "Feed (kg)",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {num(r.feed_kg, 3)}
                </span>
            ),
        },
        {
            key: "start",
            label: "Start (g)",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {r.start_g > 0 ? num(r.start_g, 2) : "—"}
                </span>
            ),
        },
        {
            key: "current",
            label: "Current (g)",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {r.current_g > 0 ? num(r.current_g, 2) : "—"}
                </span>
            ),
        },
        {
            key: "gain",
            label: "Gain (kg)",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {num(r.gain_kg, 3)}
                </span>
            ),
        },
        {
            key: "fcr",
            label: "FCR",
            align: "center",
            render: (r) => <Badge tone={r.band_tone}>{r.fcr_display}</Badge>,
        },
        {
            key: "note",
            label: "Note",
            render: (r) => (
                <span className="text-sm text-muted">{r.reason}</span>
            ),
        },
    ];

    const healthColumns = [
        {
            key: "label",
            label: "Health status",
            render: (r) => <Badge tone={r.tone}>{r.label}</Badge>,
        },
        {
            key: "count",
            label: "Inspections",
            align: "right",
            render: (r) => (
                <span className="font-medium text-text">{num(r.count, 0)}</span>
            ),
        },
    ];

    const healthRows = Object.entries(healthStatuses).map(([key, meta]) => ({
        label: meta.label,
        tone: meta.tone,
        count: inspectionStatusCounts[key] ?? 0,
    }));

    return (
        <>
            <Head title="FCR Reports" />

            <PageHeader
                title="FCR Reports"
                subtitle="Feed conversion and growth, date-ranged. Every figure is computed from recorded movements."
                breadcrumb={[
                    { label: "FCR & Growth" },
                    { label: "FCR Reports" },
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
                <Card title="Report filters">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <Field label="Pond" name="pond">
                            <Select
                                name="pond"
                                value={pond}
                                onChange={(e) => setPond(e.target.value)}
                                placeholder="All ponds"
                                options={pondOptions}
                            />
                        </Field>

                        <Field
                            label="From"
                            name="from"
                            hint="Limits the feed counted."
                        >
                            <DatePicker
                                name="from"
                                value={from}
                                onChange={(e) => setFrom(e.target.value)}
                            />
                        </Field>

                        <Field label="To" name="to">
                            <DatePicker
                                name="to"
                                value={to}
                                onChange={(e) => setTo(e.target.value)}
                            />
                        </Field>

                        <div className="flex items-end gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={busy}
                            >
                                Run report
                            </Button>
                            {hasFilters && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={clear}
                                    disabled={busy}
                                >
                                    Clear
                                </Button>
                            )}
                        </div>
                    </form>
                </Card>
            </div>

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    label="Ponds in report"
                    value={num(summary.ponds ?? 0, 0)}
                    icon="droplet"
                    tone="primary"
                />
                <KpiCard
                    label="With computable FCR"
                    value={`${num(summary.with_fcr ?? 0, 0)} of ${num(summary.ponds ?? 0, 0)}`}
                    icon="chart"
                    tone={(summary.with_fcr ?? 0) === 0 ? "warning" : "success"}
                />
                <KpiCard
                    label="Average FCR"
                    value={
                        summary.average_fcr === null
                            ? "—"
                            : num(summary.average_fcr, 2)
                    }
                    icon="chart"
                    tone={summary.average_fcr === null ? "warning" : "primary"}
                    hint={
                        summary.average_fcr === null
                            ? "No pond has a computable FCR in range"
                            : "Across ponds with a value"
                    }
                />
                <KpiCard
                    label="Total weight gain"
                    value={`${num(summary.total_gain_kg ?? 0, 3)} kg`}
                    icon="book"
                    tone="default"
                    hint={`${num(summary.total_feed_kg ?? 0, 3)} kg feed consumed`}
                />
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Pond FCR detail"
                    actions={<Badge tone="info">{rows.length} pond(s)</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={rows}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No ponds match this report
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Create a pond, or clear the pond filter.
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Inspection health context"
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
                    <DataTable columns={healthColumns} rows={healthRows} />
                </Card>
            </div>

            <div className="mt-5 surface-card border-info/30 p-4 text-sm text-text-soft">
                <strong className="text-text">
                    FCR = feed consumed ÷ weight gain.
                </strong>{" "}
                A pond shows <strong>—</strong> where the ratio cannot honestly
                be computed; the <em>Note</em> column states why. Zero is never
                shown in place of an unavailable FCR.
            </div>
        </>
    );
}

export default withLayout(FcrReports, "FCR Reports");
