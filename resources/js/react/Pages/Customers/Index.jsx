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

/** Customers list — buyers with their live derived outstanding balance. */
function CustomersIndex({
    customers,
    options = {},
    filters = {},
    summary = {},
}) {
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
            routes["customers.index"] || window.location.pathname,
            { search, status },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["customers.index"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = search !== "" || status !== "";

    const exportParams = new URLSearchParams(
        Object.entries({ search, status }).filter(([, v]) => v !== ""),
    ).toString();
    const exportHref = routes["customers.export"]
        ? exportParams
            ? `${routes["customers.export"]}?${exportParams}`
            : routes["customers.export"]
        : "";

    const columns = [
        {
            key: "name",
            label: "Customer",
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
            label: "Due",
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
                    {can("sales.view") && routes["sales.list"] && (
                        <Button
                            href={`${routes["sales.list"]}?customer=${r.id}`}
                            variant="outline"
                            size="sm"
                        >
                            Sales
                        </Button>
                    )}
                    {can("customer.payment.create") &&
                        routes["customers.payments.create"] && (
                            <Button
                                href={`${routes["customers.payments.create"]}?customer=${r.id}`}
                                variant="ghost"
                                size="sm"
                            >
                                Payment
                            </Button>
                        )}
                    {can("customer.update") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="ghost" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("customer.delete") &&
                        (r.deletable && r.urls?.destroy ? (
                            <Button
                                variant="danger"
                                size="sm"
                                onClick={async () => {
                                    if (
                                        !(await confirm({ title: "Confirm", description: `Delete customer "${r.name}"? This cannot be undone.` }))
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
                                title="Has sales or payments — mark inactive instead"
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
            <Head title="Customers" />

            <PageHeader
                title="Customers"
                subtitle="Buyers of fish, with their live outstanding balance."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Customers" },
                ]}
                actions={
                    <>
                        {routes["customers.dues"] && (
                            <Button
                                href={routes["customers.dues"]}
                                variant="outline"
                                icon="report"
                            >
                                Customer Due
                            </Button>
                        )}
                        {routes["customers.payments.index"] && (
                            <Button
                                href={routes["customers.payments.index"]}
                                variant="outline"
                                icon="download"
                            >
                                Payments
                            </Button>
                        )}
                        {exportHref && <ExportButton href={exportHref} />}
                        {can("customer.create") &&
                            routes["customers.create"] && (
                                <Button
                                    href={routes["customers.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    New Customer
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <KpiCard
                    label="Total receivable"
                    value={money(summary.totalReceivable)}
                    icon="users"
                    tone="warning"
                    hint="Still owed by all customers"
                />
                <KpiCard
                    label="Customers"
                    value={num(customers.total, 0)}
                    icon="users"
                    tone="primary"
                    hint="Matching the current filters"
                />
                <KpiCard
                    label="Active"
                    value={num(summary.activeCount, 0)}
                    icon="search"
                    tone="success"
                    hint="Available for new sales"
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
                    title="All customers"
                    actions={<Badge tone="info">{customers.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={customers.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No customers found
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or add the farm's first
                                    customer.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={customers} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(CustomersIndex, "Customers");
