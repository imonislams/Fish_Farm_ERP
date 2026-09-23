import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, num, usePermission } from "../../Components/Page";
import { useConfirm } from "../../Components/ConfirmModal";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import { DataTable, Pagination } from "../../Components/DataTable";
import Button from "../../Components/Button";
import { Field, Input, Select } from "../../Components/Form";
import { ExportButton } from "../../Components/ReportFilters";

/**
 * Ponds index — the migrated Pond Management list.
 *
 * Filters are applied SERVER-SIDE (the browser never downloads the whole table):
 * submitting issues an Inertia GET and the URL keeps the query string, so filters
 * survive refresh / back / pagination (brief §17 / §24).
 *
 * Permission checks here are DISPLAY ONLY — every route still carries
 * `permission:pond.*` middleware and the policy is re-asserted per record.
 */
function PondsIndex({
    ponds,
    typeOptions = {},
    statusOptions = {},
    filters = {},
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [search, setSearch] = React.useState(filters.search || "");
    const [type, setType] = React.useState(filters.type ?? "");
    const [status, setStatus] = React.useState(filters.status || "");
    const [busy, setBusy] = React.useState(false);

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["ponds.index"] || window.location.pathname,
            { search, type, status },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["ponds.index"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = search !== "" || status !== "" || type !== "";

    // CSV export reuses the current query string so the file matches the screen.
    const exportParams = new URLSearchParams(
        Object.entries({ search, type, status }).filter(([, v]) => v !== ""),
    ).toString();
    const exportHref = routes["ponds.export"]
        ? exportParams
            ? `${routes["ponds.export"]}?${exportParams}`
            : routes["ponds.export"]
        : "";

    const columns = [
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
            key: "size",
            label: "Size",
            align: "right",
            render: (r) => <span className="whitespace-nowrap">{r.size}</span>,
        },
        {
            key: "species",
            label: "Species",
            render: (r) =>
                r.species && r.species.length > 0 ? (
                    <div className="flex flex-wrap gap-1.5">
                        {r.species.map((s) => (
                            <Badge key={s} tone="info">
                                {s}
                            </Badge>
                        ))}
                    </div>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
        {
            key: "stocked",
            label: "Stock",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap tabular-nums">
                    {num(r.stocked, 0)}
                </span>
            ),
        },
        {
            key: "live",
            label: "Live Fish",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap font-medium tabular-nums text-text">
                    {num(r.live, 0)}
                </span>
            ),
        },
        {
            key: "biomass_kg",
            label: "Biomass",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap tabular-nums">
                    {r.biomass_kg === null ? "—" : `${num(r.biomass_kg, 2)} kg`}
                </span>
            ),
        },
        {
            key: "fcr",
            label: "FCR",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap tabular-nums">
                    {r.fcr ?? "—"}
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
            render: (r) => (
                <Badge tone={r.status_tone} dot>
                    {r.status}
                </Badge>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    <Button href={r.urls?.show} variant="outline" size="sm">
                        View
                    </Button>
                    {can("pond.update") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="ghost" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("pond.delete") && r.urls?.destroy && (
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={async () => {
                                if (
                                    !(await confirm({ title: "Confirm", description: `Delete pond "${r.name}"? This cannot be undone.` }))
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
            <Head title="All Ponds" />

            <PageHeader
                title="All Ponds"
                subtitle="Every pond on the farm, with its type, measurements and current status."
                breadcrumb={[
                    { label: "Pond Management" },
                    { label: "All Ponds" },
                ]}
                actions={
                    <>
                        {can("pond_type.view") &&
                            routes["ponds.types.index"] && (
                                <Button
                                    href={routes["ponds.types.index"]}
                                    variant="outline"
                                    icon="book"
                                >
                                    Pond Types
                                </Button>
                            )}
                        {can("pond.view") && exportHref && (
                            <ExportButton href={exportHref} />
                        )}
                        {can("pond.create") && routes["ponds.create"] && (
                            <Button
                                href={routes["ponds.create"]}
                                variant="secondary"
                                icon="droplet"
                            >
                                New Pond
                            </Button>
                        )}
                    </>
                }
            />

            <div className="mt-5">
                <Card title="Search &amp; filters">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <Field label="Search" name="search">
                            <Input
                                name="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Pond number, name, location…"
                            />
                        </Field>

                        <Field label="Pond type" name="type">
                            <Select
                                name="type"
                                value={type}
                                onChange={(e) => setType(e.target.value)}
                                placeholder="All types"
                                options={typeOptions}
                            />
                        </Field>

                        <Field label="Status" name="status">
                            <Select
                                name="status"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                placeholder="All statuses"
                                options={statusOptions}
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

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Ponds"
                    actions={<Badge tone="info">{ponds.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={ponds.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No ponds found
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or create the farm's
                                    first pond.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={ponds} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(PondsIndex, "All Ponds");
