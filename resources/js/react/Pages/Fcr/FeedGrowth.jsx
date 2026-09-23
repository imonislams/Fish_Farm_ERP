import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, num } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import { DataTable } from "../../Components/DataTable";
import Button from "../../Components/Button";
import { Field, Select } from "../../Components/Form";

/** Feed vs Growth — feed consumed against the weight gain it produced. */
function FeedGrowth({
    pondRows = [],
    pondOptions = {},
    selectedPond = null,
    feedSeries = [],
    filters = {},
}) {
    const { props } = usePage();
    const routes = props.routes || {};

    const [pond, setPond] = React.useState(filters.pond ?? "");
    const [months, setMonths] = React.useState(String(filters.months ?? 6));
    const [busy, setBusy] = React.useState(false);

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["fcr.feed-growth"] || window.location.pathname,
            { pond, months },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["fcr.feed-growth"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const feedTotal = feedSeries.reduce(
        (a, p) => a + Number(p.feed_kg || 0),
        0,
    );

    const seriesColumns = [
        {
            key: "label",
            label: "Month",
            render: (r) => <span className="text-text-soft">{r.label}</span>,
        },
        {
            key: "feed_kg",
            label: "Feed consumed",
            align: "right",
            render: (r) =>
                r.feed_kg > 0 ? (
                    <Badge tone="primary">{`${num(r.feed_kg, 3)} kg`}</Badge>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
    ];

    const rowsColumns = [
        {
            key: "pond",
            label: "Pond",
            render: (r) => (
                <span className="font-medium text-text">{r.pond}</span>
            ),
        },
        {
            key: "feed",
            label: "Feed consumed",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">{`${num(r.feed_kg, 3)} kg`}</span>
            ),
        },
        {
            key: "first",
            label: "First weight",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {r.first_g === null ? "—" : `${num(r.first_g, 2)} g`}
                </span>
            ),
        },
        {
            key: "latest",
            label: "Latest weight",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {r.latest_g === null ? "—" : `${num(r.latest_g, 2)} g`}
                </span>
            ),
        },
        {
            key: "gain",
            label: "Weight gain",
            align: "right",
            render: (r) =>
                r.gain_kg == null ? (
                    <span className="text-muted">—</span>
                ) : (
                    <Badge tone="success">{`${num(r.gain_kg, 3)} kg`}</Badge>
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
    ];

    return (
        <>
            <Head title="Feed vs Growth" />

            <PageHeader
                title="Feed vs Growth"
                subtitle="Feed consumed and the weight gain it produced — the story behind each pond's FCR."
                breadcrumb={[
                    { label: "FCR & Growth" },
                    { label: "Feed vs Growth" },
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
                <Card title="Pond selection">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <Field
                            label="Pond"
                            name="pond"
                            hint="Pick a pond to see its monthly feed trend."
                        >
                            <Select
                                name="pond"
                                value={pond}
                                onChange={(e) => setPond(e.target.value)}
                                placeholder="Select a pond…"
                                options={pondOptions}
                            />
                        </Field>

                        <Field label="Months" name="months">
                            <Select
                                name="months"
                                value={months}
                                onChange={(e) => setMonths(e.target.value)}
                                options={{
                                    3: "Last 3 months",
                                    6: "Last 6 months",
                                    12: "Last 12 months",
                                }}
                            />
                        </Field>

                        <div className="flex items-end gap-2 sm:col-span-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={busy}
                            >
                                Apply
                            </Button>
                            {filters.pond && (
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

            {selectedPond && (
                <div className="mt-5">
                    <Card
                        padded={false}
                        title={`Monthly feed consumed — ${selectedPond.name}`}
                        actions={
                            <Badge tone="info">
                                {feedTotal > 0
                                    ? `${num(feedTotal, 3)} kg total`
                                    : "No feed recorded"}
                            </Badge>
                        }
                    >
                        <DataTable
                            columns={seriesColumns}
                            rows={feedSeries}
                            empty={
                                <div className="px-6 py-12 text-center">
                                    <h4 className="text-sm font-semibold text-text">
                                        No feed recorded in this period
                                    </h4>
                                    <p className="mt-1 text-sm text-muted">
                                        No feed usage was recorded for this pond
                                        in the selected months.
                                    </p>
                                </div>
                            }
                        />
                    </Card>
                </div>
            )}

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Feed consumed vs weight gained"
                    actions={<Badge tone="info">{pondRows.length} ponds</Badge>}
                >
                    <DataTable
                        columns={rowsColumns}
                        rows={pondRows}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No ponds yet
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Create a pond and record feed usage to see
                                    this comparison.
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>

            <div className="mt-5 surface-card border-info/30 p-4 text-sm text-text-soft">
                Weight gain is measured from{" "}
                <strong className="text-text">sampled growth weights</strong>{" "}
                against the stocking weight. A pond with no growth sample shows{" "}
                <strong>—</strong> for its gain and FCR — recording samples
                under <strong>Growth Monitoring</strong> makes them computable.
            </div>
        </>
    );
}

export default withLayout(FeedGrowth, "Feed vs Growth");
