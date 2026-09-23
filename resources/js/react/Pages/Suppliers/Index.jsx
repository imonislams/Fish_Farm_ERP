import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money, num, usePermission } from "../../Components/Page";
import { useConfirm } from "../../Components/ConfirmModal";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../Components/Card";
import { DataTable, Pagination } from "../../Components/DataTable";
import Button from "../../Components/Button";
import { Field, Input, Select } from "../../Components/Form";
import { ExportButton } from "../../Components/ReportFilters";

/** Suppliers list — vendors with their live derived payable. */
function SuppliersIndex({ suppliers, filters = {}, summary = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [search, setSearch] = React.useState(filters.search || "");
    const [status, setStatus] = React.useState(filters.status || "");
    const [busy, setBusy] = React.useState(false);

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["suppliers.index"] || window.location.pathname,
            { search, status },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["suppliers.index"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = search !== "" || status !== "";

    const exportParams = new URLSearchParams(
        Object.entries({ search, status }).filter(([, v]) => v !== ""),
    ).toString();
    const exportHref = routes["suppliers.export"]
        ? exportParams
            ? `${routes["suppliers.export"]}?${exportParams}`
            : routes["suppliers.export"]
        : "";

    const columns = [
        {
            key: "name",
            label: "Supplier",
            render: (r) => (
                <div>
                    <p className="font-medium text-text">{r.name}</p>
                    {r.address && (
                        <p className="mt-0.5 text-xs text-muted">{r.address}</p>
                    )}
                </div>
            ),
        },
        {
            key: "contact",
            label: "Contact",
            render: (r) => (
                <div className="text-text-soft">
                    {r.phone || "—"}
                    {r.email && (
                        <p className="mt-0.5 text-xs text-muted">{r.email}</p>
                    )}
                </div>
            ),
        },
        {
            key: "opening",
            label: "Opening",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-muted">
                    {money(r.opening)}
                </span>
            ),
        },
        {
            key: "due",
            label: "Payable",
            align: "right",
            render: (r) => (
                <Badge
                    tone={
                        r.due > 0 ? "warning" : r.due < 0 ? "info" : "default"
                    }
                >
                    {money(r.due)}
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
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {routes["suppliers.purchases.index"] && (
                        <Button
                            href={`${routes["suppliers.purchases.index"]}?supplier=${r.id}`}
                            variant="outline"
                            size="sm"
                        >
                            Purchases
                        </Button>
                    )}
                    {can("supplier.payment.create") &&
                        routes["suppliers.payments.create"] && (
                            <Button
                                href={`${routes["suppliers.payments.create"]}?supplier=${r.id}`}
                                variant="ghost"
                                size="sm"
                            >
                                Payment
                            </Button>
                        )}
                    {can("supplier.update") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="ghost" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("supplier.delete") &&
                        (r.deletable && r.urls?.destroy ? (
                            <Button
                                variant="danger"
                                size="sm"
                                onClick={async () => {
                                    if (
                                        !(await confirm({ title: "Confirm", description: `Delete supplier "${r.name}"? This cannot be undone.` }))
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
                                title="Has purchases or payments — mark inactive instead"
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
            <Head title="Suppliers" />

            <PageHeader
                title="Suppliers"
                subtitle="Vendors the farm buys from, with their live outstanding payable."
                breadcrumb={[
                    { label: "Suppliers" },
                    { label: "Supplier List" },
                ]}
                actions={
                    <>
                        {routes["suppliers.dues"] && (
                            <Button
                                href={routes["suppliers.dues"]}
                                variant="outline"
                                icon="report"
                            >
                                Supplier Due
                            </Button>
                        )}
                        {routes["suppliers.payments.index"] && (
                            <Button
                                href={routes["suppliers.payments.index"]}
                                variant="outline"
                                icon="download"
                            >
                                Payments
                            </Button>
                        )}
                        {exportHref && <ExportButton href={exportHref} />}
                        {can("supplier.create") &&
                            routes["suppliers.create"] && (
                                <Button
                                    href={routes["suppliers.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    New Supplier
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <KpiCard
                    label="Total payable"
                    value={money(summary.totalPayable)}
                    icon="truck"
                    tone="warning"
                    hint="Still owed to suppliers"
                />
                <KpiCard
                    label="Suppliers"
                    value={num(suppliers.total, 0)}
                    icon="users"
                    tone="primary"
                    hint="Matching the current filters"
                />
                <KpiCard
                    label="Active"
                    value={num(summary.activeCount, 0)}
                    icon="search"
                    tone="success"
                    hint="Available for new purchases"
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
                                    placeholder="Name, phone or email…"
                                />
                            </Field>
                        </div>

                        <Field label="Status" name="status">
                            <Select
                                name="status"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                placeholder="All"
                                options={{
                                    active: "Active",
                                    inactive: "Inactive",
                                }}
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
                    title="All suppliers"
                    actions={<Badge tone="info">{suppliers.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={suppliers.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No suppliers found
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or add the farm's first
                                    supplier.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={suppliers} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(SuppliersIndex, "Suppliers");
