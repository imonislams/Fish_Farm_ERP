import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, num, date, usePermission } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, Input, Select } from "../../../Components/Form";
import { useConfirm } from "../../../Components/ConfirmModal";

/**
 * Fish batches (stocking cycles). Current quantity and survival are DERIVED from
 * the movement records tagged to each batch — never stored.
 */
function BatchesIndex({
    batches,
    summary = {},
    options = {},
    statusOptions = {},
    filters = {},
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();
    const fmtDate = date;

    const [search, setSearch] = React.useState(filters.search || "");
    const [status, setStatus] = React.useState(filters.status || "");
    const [pond, setPond] = React.useState(filters.pond || "");
    const [busy, setBusy] = React.useState(false);

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["fish.batches.index"],
            { search, status, pond },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["fish.batches.index"],
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = search !== "" || status !== "" || pond !== "";

    const columns = [
        {
            key: "code",
            label: "Batch",
            render: (r) => (
                <div>
                    <p className="font-medium text-text">{r.code}</p>
                    <p className="mt-0.5 text-xs text-muted">
                        Started {fmtDate(r.started_on)}
                    </p>
                </div>
            ),
        },
        {
            key: "pond",
            label: "Pond",
            render: (r) => (
                <span>
                    {r.pond_number ? `${r.pond_number} — ` : ""}
                    {r.pond || "—"}
                </span>
            ),
        },
        { key: "species", label: "Species", render: (r) => r.species || "—" },
        {
            key: "initial",
            label: "Stocked",
            align: "right",
            render: (r) => <span>{num(r.initial_quantity, 0)}</span>,
        },
        {
            key: "current",
            label: "Live",
            align: "right",
            render: (r) => (
                <span className="font-medium text-text">
                    {num(r.current_quantity, 0)}
                </span>
            ),
        },
        {
            key: "survival",
            label: "Survival",
            align: "right",
            render: (r) =>
                r.survival === null ? (
                    <span className="text-muted">—</span>
                ) : (
                    <Badge tone={r.survival >= 90 ? "success" : "warning"}>
                        {num(r.survival, 1)}%
                    </Badge>
                ),
        },
        {
            key: "status",
            label: "Status",
            align: "center",
            render: (r) => <Badge tone={r.status_tone}>{r.status}</Badge>,
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {r.urls?.show && (
                        <Button href={r.urls.show} variant="outline" size="sm">
                            View
                        </Button>
                    )}
                    {can("fish.batch.manage") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="ghost" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("fish.batch.manage") && r.urls?.destroy && (
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={async () => {
                                if (
                                    !(await confirm({
                                        title: "Confirm",
                                        description: `Delete batch "${r.code}"? This cannot be undone.`,
                                    }))
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
            <Head title="Fish Batches" />

            <PageHeader
                title="Fish Batches"
                subtitle="Stocking cycles per pond and species. Quantities come from the movement records."
                breadcrumb={[{ label: "Fish Stock" }, { label: "Batches" }]}
                actions={
                    can("fish.batch.manage") &&
                    routes["fish.batches.create"] && (
                        <Button
                            href={routes["fish.batches.create"]}
                            variant="secondary"
                            icon="plus"
                        >
                            New Batch
                        </Button>
                    )
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <KpiCard
                    label="Active batches"
                    value={num(summary.activeCount, 0)}
                    icon="fish"
                    tone="primary"
                    hint="Open cycles"
                />
                <KpiCard
                    label="Live fish (active)"
                    value={num(summary.activeFish, 0)}
                    icon="droplet"
                    tone="success"
                    hint="Across active cycles"
                />
                <KpiCard
                    label="All batches"
                    value={num(summary.total, 0)}
                    icon="book"
                    tone="default"
                    hint="Matching the filters"
                />
            </div>

            <div className="mt-5">
                <Card title="Search &amp; filters">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div className="sm:col-span-2">
                            <Field label="Search" name="search">
                                <Input
                                    name="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Batch code…"
                                />
                            </Field>
                        </div>

                        <Field label="Status" name="status">
                            <Select
                                name="status"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                placeholder="All"
                                options={statusOptions}
                            />
                        </Field>

                        <Field label="Pond" name="pond">
                            <Select
                                name="pond"
                                value={pond}
                                onChange={(e) => setPond(e.target.value)}
                                placeholder="All ponds"
                                options={options.pondOptions || {}}
                            />
                        </Field>

                        <div className="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
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

            <div className="mt-5">
                <Card
                    padded={false}
                    title="All batches"
                    actions={<Badge tone="info">{batches.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={batches.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No batches yet
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Start a stocking cycle to track a pond's
                                    fish from stocking through to harvest.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={batches} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(BatchesIndex, "Fish Batches");
