import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import {
    withLayout,
    money,
    date,
    usePermission,
} from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, DatePicker, Select } from "../../../Components/Form";
import { ExportButton } from "../../../Components/ReportFilters";

/** Party Transactions — debit/credit entries against parties. */
function PartyTransactions({ transactions, options = {}, filters = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [values, setValues] = React.useState({
        party: filters.party ?? "",
        entry_type: filters.entry_type ?? "",
        from: filters.from ?? "",
        to: filters.to ?? "",
    });
    const [busy, setBusy] = React.useState(false);

    const set = (k, v) => setValues((s) => ({ ...s, [k]: v }));

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["parties.transactions"] || window.location.pathname,
            values,
            {
                preserveState: true,
                onFinish: () => setBusy(false),
            },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["parties.transactions"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = Object.values(values).some((v) => v !== "" && v != null);

    const exportParams = new URLSearchParams(
        Object.entries(values).filter(([, v]) => v !== "" && v != null),
    ).toString();
    const exportHref = routes["parties.transactions.export"]
        ? exportParams
            ? `${routes["parties.transactions.export"]}?${exportParams}`
            : routes["parties.transactions.export"]
        : "";

    const columns = [
        {
            key: "date",
            label: "Date",
            render: (r) => (
                <span className="whitespace-nowrap">{date(r.date)}</span>
            ),
        },
        {
            key: "party",
            label: "Party",
            render: (r) => (
                <span className="font-medium text-text">{r.party}</span>
            ),
        },
        {
            key: "type",
            label: "Type",
            align: "center",
            render: (r) => <Badge tone={r.type_tone}>{r.type}</Badge>,
        },
        {
            key: "debit",
            label: "Debit",
            align: "right",
            render: (r) => (
                <span
                    className={`whitespace-nowrap font-medium ${r.is_debit ? "text-danger" : "text-muted"}`}
                >
                    {r.is_debit ? money(r.amount) : "—"}
                </span>
            ),
        },
        {
            key: "credit",
            label: "Credit",
            align: "right",
            render: (r) => (
                <span
                    className={`whitespace-nowrap font-medium ${r.is_debit ? "text-muted" : "text-success"}`}
                >
                    {!r.is_debit ? money(r.amount) : "—"}
                </span>
            ),
        },
        {
            key: "reference",
            label: "Reference",
            render: (r) => (
                <span className="text-muted">{r.reference || "—"}</span>
            ),
        },
        {
            key: "description",
            label: "Description",
            render: (r) => (
                <span className="text-text-soft">{r.description || "—"}</span>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) =>
                can("party.transaction.create") &&
                r.urls?.destroy && (
                    <Button
                        variant="danger"
                        size="sm"
                        onClick={async () => {
                            if (
                                !(await confirm({ title: "Confirm", description: "Delete this transaction? The party balance will change." }))
                            )
                                return;
                            router.delete(r.urls.destroy, {
                                preserveScroll: true,
                            });
                        }}
                    >
                        Delete
                    </Button>
                ),
        },
    ];

    return (
        <>
            <Head title="Party Transactions" />

            <PageHeader
                title="Party Transactions"
                subtitle="Debit and credit entries against parties. A balance is always derived, never stored."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Party Transactions" },
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
                        {exportHref && <ExportButton href={exportHref} />}
                        {can("party.transaction.create") &&
                            routes["parties.transactions.create"] && (
                                <Button
                                    href={routes["parties.transactions.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    New Entry
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
                        <Field label="Party" name="party">
                            <Select
                                name="party"
                                value={values.party}
                                onChange={(e) => set("party", e.target.value)}
                                placeholder="All parties"
                                options={options.partyOptions || {}}
                            />
                        </Field>

                        <Field label="Entry type" name="entry_type">
                            <Select
                                name="entry_type"
                                value={values.entry_type}
                                onChange={(e) =>
                                    set("entry_type", e.target.value)
                                }
                                placeholder="All entries"
                                options={{ debit: "Debit", credit: "Credit" }}
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

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Transaction records"
                    actions={
                        <Badge tone="info">{transactions.total} total</Badge>
                    }
                >
                    <DataTable
                        columns={columns}
                        rows={transactions.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No transactions recorded
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or record the first
                                    party transaction.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={transactions} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(PartyTransactions, "Party Transactions");
