import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, Input } from "../../../Components/Form";

/** Pond Types index — searchable list with pond counts. */
function PondTypesIndex({ types, filters = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [search, setSearch] = React.useState(filters.search || "");
    const [busy, setBusy] = React.useState(false);

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["ponds.types.index"] || window.location.pathname,
            { search },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const columns = [
        {
            key: "name",
            label: "Name",
            render: (r) => (
                <span className="font-medium text-text">{r.name}</span>
            ),
        },
        {
            key: "description",
            label: "Description",
            render: (r) => (
                <span className="text-muted">{r.description || "—"}</span>
            ),
        },
        {
            key: "ponds_count",
            label: "Ponds",
            align: "center",
            render: (r) => (
                <Badge tone={r.ponds_count > 0 ? "primary" : "default"}>
                    {r.ponds_count}
                </Badge>
            ),
        },
        {
            key: "is_active",
            label: "Status",
            align: "center",
            render: (r) =>
                r.is_active ? (
                    <Badge tone="success" dot>
                        Active
                    </Badge>
                ) : (
                    <Badge tone="danger" dot>
                        Inactive
                    </Badge>
                ),
        },
        {
            key: "created_at",
            label: "Created",
            render: (r) => (
                <span className="whitespace-nowrap">{r.created_at || "—"}</span>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {can("pond_type.update") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="outline" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("pond_type.delete") &&
                        (r.ponds_count === 0 && r.urls?.destroy ? (
                            <Button
                                variant="danger"
                                size="sm"
                                onClick={async () => {
                                    if (
                                        !(await confirm({ title: "Confirm", description: `Delete pond type "${r.name}"? This cannot be undone.` }))
                                    )
                                        return;
                                    router.delete(r.urls.destroy, {
                                        preserveScroll: true,
                                    });
                                }}
                            >
                                Delete
                            </Button>
                        ) : (
                            <Button
                                href={r.urls?.edit}
                                variant="ghost"
                                size="sm"
                                title={`In use by ${r.ponds_count} pond(s) — reassign them first`}
                            >
                                In use
                            </Button>
                        ))}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Pond Types" />

            <PageHeader
                title="Pond Types"
                subtitle="Master data classifying ponds — nursery, grow-out, brood, quarantine."
                breadcrumb={[
                    { label: "Pond Management" },
                    { label: "Pond Types" },
                ]}
                actions={
                    <>
                        {routes["ponds.index"] && (
                            <Button
                                href={routes["ponds.index"]}
                                variant="outline"
                                icon="droplet"
                            >
                                All Ponds
                            </Button>
                        )}
                        {can("pond_type.create") &&
                            routes["ponds.types.create"] && (
                                <Button
                                    href={routes["ponds.types.create"]}
                                    variant="secondary"
                                    icon="book"
                                >
                                    New Pond Type
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5">
                <Card title="Search">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-3"
                    >
                        <div className="sm:col-span-2">
                            <Field label="Search" name="search">
                                <Input
                                    name="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Type name…"
                                />
                            </Field>
                        </div>
                        <div className="flex items-end gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={busy}
                            >
                                Apply
                            </Button>
                            {search !== "" && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => {
                                        setSearch("");
                                        setBusy(true);
                                        router.get(
                                            routes["ponds.types.index"] ||
                                                window.location.pathname,
                                            {},
                                            {
                                                onFinish: () => setBusy(false),
                                            },
                                        );
                                    }}
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
                    title="All pond types"
                    actions={<Badge tone="info">{types.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={types.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No pond types found
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or create the farm's
                                    first pond type.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={types} />
                </Card>
            </div>

            <div className="mt-5 surface-card border border-info/30 p-4 text-sm text-text-soft">
                A pond type{" "}
                <strong className="text-text">
                    in use by any pond cannot be deleted
                </strong>{" "}
                — the server refuses it and explains which ponds depend on it.
                Reassign those ponds first, or mark the type <em>inactive</em>{" "}
                to stop it being offered for new ponds.
            </div>
        </>
    );
}

export default withLayout(PondTypesIndex, "Pond Types");
