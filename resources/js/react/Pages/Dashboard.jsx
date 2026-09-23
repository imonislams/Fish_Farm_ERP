import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money, num } from "../Components/Page";
import { PageHeader } from "../Components/PageHeader";
import { Card, KpiCard, Badge } from "../Components/Card";
import EmptyState from "../Components/EmptyState";
import Button from "../Components/Button";
import Icon from "../Components/Icon";

/**
 * Dashboard — 100% real database data.
 *
 * All figures are computed server-side by DashboardService; this page only renders
 * what Laravel returns. The date filter is a normal Inertia GET (no full reload).
 */
const RANGES = [
    { key: "today", label: "Today" },
    { key: "this_week", label: "This week" },
    { key: "this_month", label: "This month" },
    { key: "last_month", label: "Last month" },
    { key: "this_year", label: "This year" },
    { key: "custom", label: "Custom" },
];

function formatKpi(kpi) {
    if (kpi.format === "money") return money(kpi.value);
    if (kpi.format === "kg") return `${num(kpi.value, 3)} kg`;
    return num(kpi.value, 0);
}

function Dashboard({
    range = {},
    kpis = [],
    farmKpis = [],
    charts = {},
    lowFeedTypes = [],
    recentActivity = [],
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const [busy, setBusy] = React.useState(false);
    const [customFrom, setCustomFrom] = React.useState(range.from || "");
    const [customTo, setCustomTo] = React.useState(range.to || "");
    const [showCustom, setShowCustom] = React.useState(range.preset === "custom");

    const applyRange = (preset, extra = {}) => {
        if (preset !== "custom") setShowCustom(false);
        setBusy(true);
        router.get(
            routes["dashboard"] || window.location.pathname,
            { range: preset, ...extra },
            { preserveState: true, preserveScroll: true, onFinish: () => setBusy(false) },
        );
    };

    const salesTrend = charts.salesTrend || [];
    const expenseTrend = charts.expenseTrend || [];
    const stock = charts.stock || [];
    const feed = charts.feed || [];

    return (
        <>
            <Head title="Dashboard" />

            <PageHeader
                title="Dashboard"
                subtitle="Live farm overview — every figure comes straight from the database."
                actions={
                    <Badge tone="success" dot>
                        {range.label || "Live"}
                    </Badge>
                }
            />

            {/* Date filter — Inertia GET, no full reload */}
            <div className="mt-5 flex flex-wrap items-center gap-2">
                {RANGES.map((r) => (
                    <Button
                        key={r.key}
                        variant={range.preset === r.key ? "primary" : "outline"}
                        size="sm"
                        onClick={() =>
                            r.key === "custom"
                                ? setShowCustom((v) => !v)
                                : applyRange(r.key)
                        }
                        disabled={busy}
                    >
                        {r.label}
                    </Button>
                ))}
                {busy && <span className="text-xs text-muted">Loading…</span>}
            </div>

            {showCustom && (
                <div className="mt-3 flex flex-wrap items-end gap-3">
                    <label className="text-sm">
                        <span className="mb-1 block text-xs font-medium text-muted">
                            From
                        </span>
                        <input
                            type="date"
                            value={customFrom}
                            onChange={(e) => setCustomFrom(e.target.value)}
                            className="rounded-control border border-border-strong bg-surface px-3 py-2 text-sm"
                        />
                    </label>
                    <label className="text-sm">
                        <span className="mb-1 block text-xs font-medium text-muted">
                            To
                        </span>
                        <input
                            type="date"
                            value={customTo}
                            onChange={(e) => setCustomTo(e.target.value)}
                            className="rounded-control border border-border-strong bg-surface px-3 py-2 text-sm"
                        />
                    </label>
                    <Button
                        variant="primary"
                        size="sm"
                        onClick={() =>
                            applyRange("custom", { from: customFrom, to: customTo })
                        }
                        disabled={busy}
                    >
                        Apply
                    </Button>
                </div>
            )}

            {/* Financial KPIs (date-scoped) */}
            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {kpis.map((k) => (
                    <KpiCard
                        key={k.label}
                        label={k.label}
                        value={formatKpi(k)}
                        hint={k.hint}
                        icon={k.icon}
                        tone={
                            k.label === "Profit / Loss"
                                ? k.value < 0
                                    ? "danger"
                                    : "success"
                                : "primary"
                        }
                    />
                ))}
            </div>

            {/* Farm snapshot KPIs */}
            <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {farmKpis.map((k) => (
                    <KpiCard
                        key={k.label}
                        label={k.label}
                        value={formatKpi(k)}
                        hint={k.hint}
                        icon={k.icon}
                        tone="default"
                    />
                ))}
            </div>

            {/* Charts from real data */}
            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <TrendChart title="Sales trend" series={salesTrend} tone="primary" />
                <TrendChart title="Expense trend" series={expenseTrend} tone="danger" />
            </div>

            <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <BarCard title="Fish stock (in range)" rows={stock} />
                <BarCard title="Feed (in range)" rows={feed} unit="kg" />
            </div>

            {/* Low feed stock — real signal */}
            {lowFeedTypes.length > 0 && (
                <div className="mt-5">
                    <Card
                        padded={false}
                        title="Low feed stock"
                        actions={
                            <Badge tone="warning">
                                {lowFeedTypes.length} type(s) low
                            </Badge>
                        }
                    >
                        <div className="divide-y divide-border">
                            {lowFeedTypes.map((t) => (
                                <div
                                    key={t.label}
                                    className="flex items-center justify-between gap-3 px-4 py-3"
                                >
                                    <span className="font-medium text-text">
                                        {t.label}
                                    </span>
                                    <span className="text-sm text-muted">
                                        {num(t.value, 3)} kg · {t.hint}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </Card>
                </div>
            )}

            {/* Recent activity — real records */}
            <div className="mt-5">
                <Card
                    padded={false}
                    title="Recent activity"
                    actions={<Badge tone="info">{recentActivity.length}</Badge>}
                >
                    {recentActivity.length === 0 ? (
                        <EmptyState
                            icon="book"
                            title="No activity yet"
                            message="Sales, purchases, payments and farm events will appear here as they are recorded."
                        />
                    ) : (
                        <ul className="divide-y divide-border">
                            {recentActivity.map((e) => (
                                <li
                                    key={e.id}
                                    className="flex items-center gap-3 px-4 py-3"
                                >
                                    <span className="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-primary/10 text-primary">
                                        <Icon name={e.icon} className="h-4 w-4" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm text-text">
                                            {e.description}
                                        </p>
                                        <p className="text-xs text-muted">
                                            {e.type} · {e.at}
                                        </p>
                                    </div>
                                    {e.amount != null && (
                                        <span className="shrink-0 text-sm font-medium text-text">
                                            {money(e.amount)}
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>
        </>
    );
}

/** A compact CSS bar chart — no heavy chart dependency. */
function TrendChart({ title, series, tone = "primary" }) {
    const max = Math.max(1, ...series.map((p) => p.value));
    const total = series.reduce((a, p) => a + p.value, 0);
    const barColor = tone === "danger" ? "bg-danger/70" : "bg-primary/70";

    return (
        <Card title={title} actions={<Badge tone="info">{money(total)}</Badge>}>
            {series.length === 0 || total === 0 ? (
                <EmptyState
                    icon="chart"
                    title="No data in this range"
                    message="Pick a wider date range, or record data for this period."
                />
            ) : (
                <div className="flex h-40 items-end gap-1">
                    {series.map((p) => (
                        <div
                            key={p.date}
                            className="group relative flex-1"
                            title={`${p.label}: ${money(p.value)}`}
                        >
                            <div
                                className={`w-full rounded-t ${barColor}`}
                                style={{
                                    height: `${Math.max(2, (p.value / max) * 140)}px`,
                                }}
                            />
                        </div>
                    ))}
                </div>
            )}
        </Card>
    );
}

/** A horizontal bar breakdown from real rows. */
function BarCard({ title, rows = [], unit = "" }) {
    const max = Math.max(1, ...rows.map((r) => Number(r.value) || 0));

    return (
        <Card title={title}>
            {rows.length === 0 ? (
                <EmptyState
                    icon="chart"
                    title="No data yet"
                    message="Record data for this period to see the breakdown."
                />
            ) : (
                <div className="space-y-3">
                    {rows.map((r) => (
                        <div key={r.label}>
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-text-soft">{r.label}</span>
                                <span className="font-medium text-text">
                                    {num(r.value, unit === "kg" ? 3 : 0)}
                                    {unit ? ` ${unit}` : ""}
                                </span>
                            </div>
                            <div className="mt-1 h-2 w-full overflow-hidden rounded-full bg-surface-muted">
                                <div
                                    className="h-full rounded-full bg-primary/70"
                                    style={{
                                        width: `${Math.max(
                                            2,
                                            (Number(r.value) / max) * 100,
                                        )}%`,
                                    }}
                                />
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </Card>
    );
}

export default withLayout(Dashboard, "Dashboard");
