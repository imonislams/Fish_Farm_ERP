import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money, usePermission } from "../../Components/Page";
import { useConfirm } from "../../Components/ConfirmModal";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import { DataTable, Pagination } from "../../Components/DataTable";
import Button from "../../Components/Button";
import { Field, Input, Select } from "../../Components/Form";
import { ExportButton } from "../../Components/ReportFilters";

/** Parties list — generic ledger counterparties with their live balance. */
function PartiesIndex({ parties, typeOptions = {}, filters = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [search, setSearch] = React.useState(filters.search || "");
    const [type, setType] = React.useState(filters.type || "");
    const [busy, setBusy] = React.useState(false);

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["parties.index"] || window.location.pathname,
            { search, type },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["parties.index"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = search !== "" || type !== "";

    const exportParams = new URLSearchParams(
        Object.entries({ search, type }).filter(([, v]) => v !== ""),
    ).toString();
    const exportHref = routes["parties.export"]
        ? exportParams
            ? `${routes["parties.export"]}?${exportParams}`
            : routes["parties.export"]
        : "";

    const columns = [
        {
            key: "name",
            label: "Party",
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
            key: "type",
            label: "Type",
            render: (r) => <Badge tone="default">{r.type}</Badge>,
        },
        {
            key: "phone",
            label: "Contact",
            render: (r) => (
                <span className="text-text-soft">{r.phone || "—"}</span>
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
            key: "balance",
            label: "Balance",
            align: "right",
            render: (r) => (
                <Badge
                    tone={
                        r.balance > 0
                            ? "warning"
                            : r.balance < 0
                              ? "info"
                              : "default"
                    }
                >
                    {money(r.balance)}
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
                    {routes["parties.transactions"] && (
                        <Button
                            href={`${routes["parties.transactions"]}?party=${r.id}`}
                            variant="outline"
                            size="sm"
                        >
                            Transactions
                        </Button>
                    )}
                    {can("party.transaction.create") &&
                        routes["parties.transactions.create"] && (
                            <Button
                                href={`${routes["parties.transactions.create"]}?party=${r.id}`}
                                variant="ghost"
                                size="sm"
                            >
                                Entry
                            </Button>
                        )}
                    {can("party.update") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="ghost" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("party.delete") &&
                        (r.deletable && r.urls?.destroy ? (
                            <Button
                                variant="danger"
                                size="sm"
                                onClick={async () => {
                                    if (
                                        !(await confirm({ title: "Confirm", description: `Delete party "${r.name}"? This cannot be undone.` }))
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
                                title="Has transactions — mark inactive instead"
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
            <Head title="Parties" />

            <PageHeader
                title="Parties"
                subtitle="Generic ledger counterparties — landlords, agents, workers — with their live balance."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Parties" },
                ]}
                actions={
                    <>
                        {routes["parties.ledger"] && (
                            <Button
                                href={routes["parties.ledger"]}
                                variant="outline"
                                icon="report"
                            >
                                Party Ledger
                            </Button>
                        )}
                        {routes["parties.transactions"] && (
                            <Button
                                href={routes["parties.transactions"]}
                                variant="outline"
                                icon="book"
                            >
                                Transactions
                            </Button>
                        )}
                        {exportHref && <ExportButton href={exportHref} />}
                        {can("party.create") && routes["parties.create"] && (
                            <Button
                                href={routes["parties.create"]}
                                variant="secondary"
                                icon="plus"
                            >
                                New Party
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
                        <div className="sm:col-span-2">
                            <Field label="Search" name="search">
                                <Input
                                    name="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Name or phone…"
                                />
                            </Field>
                        </div>

                        <Field label="Type" name="type">
                            <Select
                                name="type"
                                value={type}
                                onChange={(e) => setType(e.target.value)}
                                placeholder="All types"
                                options={typeOptions}
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
                    title="All parties"
                    actions={<Badge tone="info">{parties.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={parties.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No parties found
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or add the farm's first
                                    party.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={parties} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(PartiesIndex, "Parties");
