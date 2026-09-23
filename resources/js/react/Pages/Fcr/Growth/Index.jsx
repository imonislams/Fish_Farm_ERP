import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, num, date, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, DatePicker, Select } from "../../../Components/Form";

/** Growth Monitoring — sampled average fish weights over time. */
function GrowthIndex({ records, summaries = [], options = {}, filters = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [values, setValues] = React.useState({
        pond: filters.pond ?? "",
        from: filters.from ?? "",
        to: filters.to ?? "",
    });
    const [busy, setBusy] = React.useState(false);

    const set = (k, v) => setValues((s) => ({ ...s, [k]: v }));

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(routes["fcr.growth"] || window.location.pathname, values, {
            preserveState: true,
            onFinish: () => setBusy(false),
        });
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["fcr.growth"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = Object.values(values).some((v) => v !== "" && v != null);

    const summaryColumns = [
        {
            key: "pond",
            label: "Pond",
            render: (r) => (
                <span className="font-medium text-text">{r.pond}</span>
            ),
        },
        {
            key: "samples",
            label: "Samples",
            align: "right",
            render: (r) => <span className="text-text-soft">{r.samples}</span>,
        },
        {
            key: "first_g",
            label: "First",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {`${num(r.first_g, 2)} g`}
                </span>
            ),
        },
        {
            key: "latest_g",
            label: "Latest",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap font-medium">
                    {`${num(r.latest_g, 2)} g`}
                </span>
            ),
        },
        {
            key: "gain_g",
            label: "Gain",
            align: "right",
            render: (r) =>
                r.gain_g === null ? (
                    <Badge tone="default">—</Badge>
                ) : (
                    <Badge tone={r.gain_g < 0 ? "danger" : "success"}>
                        {`${r.gain_g > 0 ? "+" : ""}${num(r.gain_g, 2)} g`}
                    </Badge>
                ),
        },
        {
            key: "daily",
            label: "Daily rate",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {r.daily_gain_g === null
                        ? "—"
                        : `${num(r.daily_gain_g, 3)} g/day`}
                </span>
            ),
        },
    ];

    const columns = [
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
            render: (r) => (
                <div>
                    <span className="font-medium text-text">{r.pond}</span>
                    {r.pond_number && (
                        <p className="mt-0.5 text-xs text-muted">
                            <code className="rounded bg-surface-muted px-1.5 py-0.5">
                                {r.pond_number}
                            </code>
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: "avg_weight",
            label: "Avg weight",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap font-medium">
                    {r.avg_weight}
                </span>
            ),
        },
        {
            key: "in_kg",
            label: "In kg",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {r.in_kg}
                </span>
            ),
        },
        {
            key: "sample_size",
            label: "Sample size",
            align: "right",
            render: (r) => <span className="text-muted">{r.sample_size}</span>,
        },
        {
            key: "note",
            label: "Note",
            render: (r) => <span className="text-muted">{r.note || "—"}</span>,
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {can("growth.create") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="outline" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("growth.create") && r.urls?.destroy && (
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={async () => {
                                if (
                                    !(await confirm({ title: "Confirm", description: "Delete this growth sample? FCR for this pond will be recalculated without it." }))
                                )
                                    return;
                                router.delete(r.urls.destroy, {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            Delete
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Growth Monitoring" />

            <PageHeader
                title="Growth Monitoring"
                subtitle="Sampled average fish weights over time. These samples are the 'current weight' FCR uses."
                breadcrumb={[
                    { label: "FCR & Growth" },
                    { label: "Growth Monitoring" },
                ]}
                actions={
                    <>
                        {routes["fcr.index"] && (
                            <Button
                                href={routes["fcr.index"]}
                                variant="outline"
                                icon="chart"
                            >
                                FCR Dashboard
                            </Button>
                        )}
                        {can("growth.create") &&
                            routes["fcr.growth.create"] && (
                                <Button
                                    href={
                                        filters.pond
                                            ? `${routes["fcr.growth.create"]}?pond=${filters.pond}`
                                            : routes["fcr.growth.create"]
                                    }
                                    variant="secondary"
                                    icon="plus"
                                >
                                    Record Sample
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5">
                <Card title="Filters">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <Field label="Pond" name="pond">
                            <Select
                                name="pond"
                                value={values.pond}
                                onChange={(e) => set("pond", e.target.value)}
                                placeholder="All ponds"
                                options={options.pondOptions || {}}
                            />
                        </Field>

                        <Field label="From" name="from">
                            <DatePicker
                                name="from"
                                value={values.from}
                                onChange={(e) => set("from", e.target.value)}
                            />
                        </Field>

                        <Field label="To" name="to">
                            <DatePicker
                                name="to"
                                value={values.to}
                                onChange={(e) => set("to", e.target.value)}
                            />
                        </Field>

                        <div className="flex items-end gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={busy}
                            >
                                Apply
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

            {summaries.length > 0 && (
                <div className="mt-5">
                    <Card
                        padded={false}
                        title="Growth summary"
                        actions={
                            <Badge tone="info">
                                {summaries.length} pond(s) sampled
                            </Badge>
                        }
                    >
                        <DataTable columns={summaryColumns} rows={summaries} />
                    </Card>
                </div>
            )}

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Growth samples"
                    actions={<Badge tone="info">{records.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={records.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No growth samples
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or record the first
                                    sampled average weight for a pond.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={records} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(GrowthIndex, "Growth Monitoring");
