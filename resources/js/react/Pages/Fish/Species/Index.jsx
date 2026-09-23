import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, Input } from "../../../Components/Form";

/** Fish Species index — searchable list with live-stock and record counts. */
function FishSpeciesIndex({ species, currency = "", filters = {} }) {
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
            routes["fish.species.index"] || window.location.pathname,
            { search },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const columns = [
        {
            key: "name",
            label: "Species",
            render: (r) => (
                <div>
                    <p className="font-medium text-text">{r.name}</p>
                    {(r.local_name || r.scientific_name) && (
                        <p className="mt-0.5 text-xs text-muted">
                            {r.local_name || ""}
                            {r.local_name && r.scientific_name ? " · " : ""}
                            {r.scientific_name && <em>{r.scientific_name}</em>}
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: "default_price_per_kg",
            label: "Price / kg",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap">
                    {r.default_price_per_kg != null
                        ? money(r.default_price_per_kg)
                        : "—"}
                </span>
            ),
        },
        {
            key: "stock",
            label: "Live fish",
            align: "right",
            render: (r) => (
                <Badge tone={r.stock > 0 ? "primary" : "default"}>
                    {Number(r.stock).toLocaleString()}
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
                    {can("fish.species.manage") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="outline" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("fish.species.manage") &&
                        (r.records === 0 && r.urls?.destroy ? (
                            <Button
                                variant="danger"
                                size="sm"
                                onClick={async () => {
                                    if (
                                        !(await confirm({ title: "Confirm", description: `Delete species "${r.name}"? This cannot be undone.` }))
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
            <Head title="Fish Species" />

            <PageHeader
                title="Fish Species"
                subtitle="The species catalogue used when recording stockings and harvests."
                breadcrumb={[
                    { label: "Fish Stock" },
                    { label: "Fish Species" },
                ]}
                actions={
                    <>
                        {routes["fish.index"] && (
                            <Button
                                href={routes["fish.index"]}
                                variant="outline"
                                icon="fish"
                            >
                                Stock Dashboard
                            </Button>
                        )}
                        {can("fish.species.manage") &&
                            routes["fish.species.create"] && (
                                <Button
                                    href={routes["fish.species.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    New Species
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
                                    placeholder="Species, local or scientific name…"
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
                                            routes["fish.species.index"] ||
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
                    title="All species"
                    actions={<Badge tone="info">{species.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={species.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No species found
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your search, or add the farm's first
                                    fish species.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={species} />
                </Card>
            </div>

            <div className="mt-5 surface-card border border-info/30 p-4 text-sm text-text-soft">
                A species{" "}
                <strong className="text-text">
                    used by any stocking or harvest cannot be deleted
                </strong>{" "}
                — the server refuses it and explains why. Mark it{" "}
                <em>inactive</em> to stop it being offered for new records while
                keeping its history.
            </div>
        </>
    );
}

export default withLayout(FishSpeciesIndex, "Fish Species");
