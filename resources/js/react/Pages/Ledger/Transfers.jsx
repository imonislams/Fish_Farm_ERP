import React from "react";
import { Head, router, useForm, usePage } from "@inertiajs/react";
import { withLayout, date, usePermission } from "../../Components/Page";
import { useConfirm } from "../../Components/ConfirmModal";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import { DataTable, Pagination } from "../../Components/DataTable";
import Button from "../../Components/Button";
import { Field, Input, Select, DatePicker } from "../../Components/Form";

/** Pond Ledger — Transfers: New transfer form + Transfer History. */
function LedgerTransfers({ transfers, options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const { data, setData, post, processing, errors, reset } = useForm({
        from_pond_id: "",
        to_pond_id: "",
        quantity: "",
        fish_species_id: "",
        transferred_on: defaultDate,
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["ledger.transfers.store"], {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const pondOptions = options.pondOptions || {};
    const speciesOptions = options.speciesOptions || {};
    const hasTwoPonds = Object.keys(pondOptions).length >= 2;

    const columns = [
        {
            key: "date",
            label: "Date",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {date(r.date)}
                </span>
            ),
        },
        {
            key: "from",
            label: "From",
            render: (r) => (
                <div>
                    <span className="font-medium text-text">{r.from}</span>
                    {r.from_number && (
                        <p className="mt-0.5 text-xs text-muted">
                            <code className="rounded bg-surface-muted px-1.5 py-0.5">
                                {r.from_number}
                            </code>
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: "to",
            label: "To",
            render: (r) => (
                <div>
                    <span className="font-medium text-text">{r.to}</span>
                    {r.to_number && (
                        <p className="mt-0.5 text-xs text-muted">
                            <code className="rounded bg-surface-muted px-1.5 py-0.5">
                                {r.to_number}
                            </code>
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: "species",
            label: "Species",
            render: (r) => <span className="text-text-soft">{r.species}</span>,
        },
        {
            key: "quantity",
            label: "Quantity",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap font-medium">
                    {r.quantity}
                </span>
            ),
        },
        {
            key: "reference",
            label: "Ref.",
            render: (r) =>
                r.reference ? (
                    <code className="rounded bg-surface-muted px-1.5 py-0.5 text-xs">
                        {r.reference}
                    </code>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
        {
            key: "user",
            label: "User",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {r.user}
                </span>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) =>
                can("pond_ledger.transfer.delete") &&
                r.urls?.destroy && (
                    <Button
                        variant="danger"
                        size="sm"
                        onClick={async () => {
                            if (
                                !(await confirm({ title: "Confirm", description: "Delete this transfer? Both ponds' stock will be restored." }))
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
            <Head title="Transfers" />

            <PageHeader
                title="Transfers"
                subtitle="Transferring fish into ponds"
                breadcrumb={[
                    { label: "Pond Ledger", href: routes["ledger.index"] },
                    { label: "Transfers" },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-1">
                    <Card
                        title="New Transfers"
                        subtitle="Move fish from one pond to another."
                    >
                        {!hasTwoPonds ? (
                            <div className="surface-card border-warning/40 p-3 text-sm text-text-soft">
                                <strong className="text-text">
                                    At least two ponds are required.
                                </strong>{" "}
                                A transfer moves fish between ponds.
                            </div>
                        ) : can("pond_ledger.transfer.create") ? (
                            <form onSubmit={submit} className="space-y-4">
                                <Field
                                    label="From Pond"
                                    name="from_pond_id"
                                    required
                                    error={errors.from_pond_id}
                                >
                                    <Select
                                        name="from_pond_id"
                                        value={data.from_pond_id}
                                        onChange={(e) =>
                                            setData(
                                                "from_pond_id",
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Select source pond…"
                                        options={pondOptions}
                                    />
                                </Field>

                                <Field
                                    label="To Pond"
                                    name="to_pond_id"
                                    required
                                    error={errors.to_pond_id}
                                >
                                    <Select
                                        name="to_pond_id"
                                        value={data.to_pond_id}
                                        onChange={(e) =>
                                            setData(
                                                "to_pond_id",
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Select destination pond…"
                                        options={pondOptions}
                                    />
                                </Field>

                                <Field
                                    label="Quantity"
                                    name="quantity"
                                    required
                                    hint="Cannot exceed the source pond's live stock."
                                    error={errors.quantity}
                                >
                                    <Input
                                        name="quantity"
                                        type="number"
                                        step="1"
                                        min="1"
                                        inputMode="numeric"
                                        value={data.quantity}
                                        onChange={(e) =>
                                            setData("quantity", e.target.value)
                                        }
                                    />
                                </Field>

                                {Object.keys(speciesOptions).length > 0 && (
                                    <Field
                                        label="Species"
                                        name="fish_species_id"
                                        hint="Optional."
                                        error={errors.fish_species_id}
                                    >
                                        <Select
                                            name="fish_species_id"
                                            value={data.fish_species_id}
                                            onChange={(e) =>
                                                setData(
                                                    "fish_species_id",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Select a species…"
                                            options={speciesOptions}
                                        />
                                    </Field>
                                )}

                                <Field
                                    label="Date"
                                    name="transferred_on"
                                    required
                                    error={errors.transferred_on}
                                >
                                    <DatePicker
                                        name="transferred_on"
                                        value={data.transferred_on}
                                        onChange={(e) =>
                                            setData(
                                                "transferred_on",
                                                e.target.value,
                                            )
                                        }
                                    />
                                </Field>

                                <Button
                                    type="submit"
                                    variant="primary"
                                    loading={processing}
                                    className="w-full"
                                >
                                    Add
                                </Button>
                            </form>
                        ) : (
                            <div className="surface-card border-info/30 p-3 text-xs text-text-soft">
                                You have view-only access to the transfer
                                records.
                            </div>
                        )}
                    </Card>
                </div>

                <div className="lg:col-span-2">
                    <Card
                        padded={false}
                        title="Transfer History"
                        actions={
                            <Badge tone="info">{transfers.total} total</Badge>
                        }
                    >
                        <DataTable
                            columns={columns}
                            rows={transfers.data}
                            empty={
                                <div className="px-6 py-12 text-center">
                                    <h4 className="text-sm font-semibold text-text">
                                        No transfers found.
                                    </h4>
                                    <p className="mt-1 text-sm text-muted">
                                        No fish have been transferred between
                                        ponds yet.
                                    </p>
                                </div>
                            }
                        />
                        <Pagination paginator={transfers} />
                    </Card>
                </div>
            </div>
        </>
    );
}

export default withLayout(LedgerTransfers, "Transfers");
