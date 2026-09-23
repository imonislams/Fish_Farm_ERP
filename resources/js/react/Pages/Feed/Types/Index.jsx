import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, num, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, Input } from "../../../Components/Form";

/** Food Types index — searchable list with current stock per type. */
function FeedTypesIndex({ types, filters = {} }) {
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
            routes["feed.types.index"] || window.location.pathname,
            { search },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const columns = [
        {
            key: "name",
            label: "Feed type",
            render: (r) => (
                <div>
                    <p className="font-medium text-text">{r.name}</p>
                    {r.brand && (
                        <p className="mt-0.5 text-xs text-muted">{r.brand}</p>
                    )}
                </div>
            ),
        },
        {
            key: "protein",
            label: "Protein",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap">
                    {r.protein != null ? `${r.protein}%` : "—"}
                </span>
            ),
        },
        {
            key: "stock",
            label: "In stock",
            align: "right",
            render: (r) => (
                <Badge
                    tone={
                        r.is_critical
                            ? "danger"
                            : r.is_low
                              ? "warning"
                              : r.stock > 0
                                ? "primary"
                                : "default"
                    }
                >
                    {`${num(r.stock, 3)} kg`}
                </Badge>
            ),
        },
        {
            key: "records",
            label: "Records",
            align: "center",
            render: (r) => <span className="text-muted">{r.records}</span>,
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
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {can("feed.type.manage") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="outline" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("feed.type.manage") &&
                        (r.records === 0 && r.urls?.destroy ? (
                            <Button
                                variant="danger"
                                size="sm"
                                onClick={async () => {
                                    if (
                                        !(await confirm({ title: "Confirm", description: `Delete feed type "${r.name}"? This cannot be undone.` }))
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
                                title="In use by stock records — mark it inactive instead"
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
            <Head title="Food Types" />

            <PageHeader
                title="Food Types"
                subtitle="The feed product catalogue. Stock is tracked per type in kilograms."
                breadcrumb={[
                    { label: "Food Management" },
                    { label: "Food Types" },
                ]}
                actions={
                    <>
                        {routes["feed.index"] && (
                            <Button
                                href={routes["feed.index"]}
                                variant="outline"
                                icon="feed"
                            >
                                Food Dashboard
                            </Button>
                        )}
                        {can("feed.type.manage") &&
                            routes["feed.types.create"] && (
                                <Button
                                    href={routes["feed.types.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    New Food Type
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
                                    placeholder="Name or brand…"
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
                                            routes["feed.types.index"] ||
                                                window.location.pathname,
                                            {},
                                            { onFinish: () => setBusy(false) },
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
                    title="All feed types"
                    actions={<Badge tone="info">{types.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={types.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No feed types found
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your search, or add the farm's first
                                    feed type.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={types} />
                </Card>
            </div>

            <div className="mt-5 surface-card border border-info/30 p-4 text-sm text-text-soft">
                A feed type{" "}
                <strong className="text-text">
                    used by any purchase, usage or adjustment cannot be deleted
                </strong>{" "}
                — the server refuses it and explains why. Mark it{" "}
                <em>inactive</em> to stop it being offered for new movements
                while keeping its stock history.
            </div>
        </>
    );
}

export default withLayout(FeedTypesIndex, "Food Types");
