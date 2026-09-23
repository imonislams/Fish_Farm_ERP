import React from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import { DataTable, Pagination } from "../../Components/DataTable";
import Button from "../../Components/Button";
import Icon from "../../Components/Icon";

/**
 * Pond Status — real per-status counts + a filtered list.
 *
 * Each card is an Inertia link that filters the list below (`?status=…`). Counts
 * are genuine `COUNT(*) ... GROUP BY status` values from PondService.
 */
function PondsStatus({
    counts = {},
    total = 0,
    statuses = {},
    selected = "",
    ponds,
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const statusUrl = (key) => {
        const base = routes["ponds.status"] || window.location.pathname;
        return key ? `${base}?status=${key}` : base;
    };

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
        { key: "type", label: "Type", render: (r) => r.type || "—" },
        { key: "size", label: "Size", align: "right" },
        { key: "depth", label: "Depth", align: "right" },
        {
            key: "location",
            label: "Location",
            render: (r) => (
                <span className="text-muted">{r.location || "—"}</span>
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
                </div>
            ),
        },
    ];

    const selectedMeta = selected ? statuses[selected] : null;

    return (
        <>
            <Head title="Pond Status" />

            <PageHeader
                title="Pond Status"
                subtitle="Where the farm's ponds stand right now. Counts come straight from the database."
                breadcrumb={[
                    { label: "Pond Management" },
                    { label: "Pond Status" },
                ]}
                actions={
                    <>
                        <Badge tone="info">{total} ponds</Badge>
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

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {Object.entries(statuses).map(([key, meta]) => {
                    const isSelected = selected === key;
                    return (
                        <Link
                            key={key}
                            href={statusUrl(key)}
                            className={`surface-card surface-card--hoverable block border p-4 transition-colors ${
                                isSelected
                                    ? "border-primary ring-1 ring-primary/30"
                                    : ""
                            }`}
                            aria-current={isSelected ? "true" : undefined}
                        >
                            <div className="flex items-center justify-between gap-3">
                                <span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-surface-muted text-muted">
                                    <Icon name="droplet" className="h-5 w-5" />
                                </span>
                                <Badge tone={meta.tone}>{meta.label}</Badge>
                            </div>

                            <p className="mt-3 text-2xl font-bold text-text">
                                {counts[key] ?? 0}
                            </p>
                            <p className="mt-0.5 text-xs text-muted">
                                {meta.description}
                            </p>

                            {meta.usable ? (
                                <p className="mt-2 text-xs font-medium text-success">
                                    Usable for stocking
                                </p>
                            ) : (
                                <p className="mt-2 text-xs font-medium text-muted">
                                    Not usable for stocking
                                </p>
                            )}
                        </Link>
                    );
                })}
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title={
                        selectedMeta
                            ? `${selectedMeta.label} ponds`
                            : "All ponds"
                    }
                    actions={
                        <>
                            <Badge tone="info">{ponds.total} total</Badge>
                            {selected && (
                                <Button
                                    href={statusUrl("")}
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
                        rows={ponds.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    {selected
                                        ? "No ponds in this status"
                                        : "No ponds yet"}
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    {selected
                                        ? `No pond currently has the “${selectedMeta?.label}” status.`
                                        : "Create the farm's first pond to see its status here."}
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

export default withLayout(PondsStatus, "Pond Status");
