import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout, date, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import { DataTable, Pagination } from "../../Components/DataTable";
import Button from "../../Components/Button";
import { Field, Input, Select, DatePicker } from "../../Components/Form";

/** Pond Ledger — Stocking section: New Stock form + Stock History. */
function LedgerStocking({
    stockings,
    options = {},
    filters = {},
    defaultDate = "",
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const { data, setData, post, processing, errors, reset } = useForm({
        pond_id: "",
        fish_species_id: "",
        quantity: "",
        stocked_on: defaultDate,
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["ledger.stocking.store"], {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const hasPonds = Object.keys(options.pondOptions || {}).length > 0;
    const hasSpecies = Object.keys(options.speciesOptions || {}).length > 0;

    const columns = [
        {
            key: "reference",
            label: "Batch",
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
            key: "pond",
            label: "Pond",
            render: (r) => (
                <div>
                    <span className="font-medium text-text">{r.pond}</span>
                    {r.pond_number && (
                        <p className="mt-0.5 text-xs text-muted">
                            <code className="rounded bg-surface-muted px-1.5 py-0.5">
                                {r.pond_number}
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
            key: "date",
            label: "Date",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {date(r.date)}
                </span>
            ),
        },
        {
            key: "quantity",
            label: "Number",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap font-medium">
                    {r.quantity}
                </span>
            ),
        },
    ];

    return (
        <>
            <Head title="Stocking" />

            <PageHeader
                title="Stocking"
                subtitle="Stock up on new fry"
                breadcrumb={[
                    { label: "Pond Ledger", href: routes["ledger.index"] },
                    { label: "Stocking" },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-1">
                    <Card title="New Stock" subtitle="Add fry to a pond.">
                        {!hasPonds ? (
                            <div className="surface-card border-warning/40 p-3 text-sm text-text-soft">
                                <strong className="text-text">
                                    No ponds exist yet.
                                </strong>{" "}
                                Create a pond first.
                            </div>
                        ) : !hasSpecies ? (
                            <div className="surface-card border-warning/40 p-3 text-sm text-text-soft">
                                <strong className="text-text">
                                    No fish species exist yet.
                                </strong>{" "}
                                A stocking must name a species.
                            </div>
                        ) : can("pond_ledger.stocking.create") ? (
                            <form onSubmit={submit} className="space-y-4">
                                <Field
                                    label="Pond"
                                    name="pond_id"
                                    required
                                    error={errors.pond_id}
                                >
                                    <Select
                                        name="pond_id"
                                        value={data.pond_id}
                                        onChange={(e) =>
                                            setData("pond_id", e.target.value)
                                        }
                                        placeholder="Select a pond…"
                                        options={options.pondOptions || {}}
                                    />
                                </Field>

                                <Field
                                    label="Species"
                                    name="fish_species_id"
                                    required
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
                                        options={options.speciesOptions || {}}
                                    />
                                </Field>

                                <Field
                                    label="Number"
                                    name="quantity"
                                    required
                                    hint="A positive whole number of fish."
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

                                <Field
                                    label="Date"
                                    name="stocked_on"
                                    required
                                    error={errors.stocked_on}
                                >
                                    <DatePicker
                                        name="stocked_on"
                                        value={data.stocked_on}
                                        onChange={(e) =>
                                            setData(
                                                "stocked_on",
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
                                    Add Stock
                                </Button>
                            </form>
                        ) : (
                            <div className="surface-card border-info/30 p-3 text-xs text-text-soft">
                                You have view-only access to the stocking
                                records.
                            </div>
                        )}
                    </Card>
                </div>

                <div className="lg:col-span-2">
                    <Card
                        padded={false}
                        title="Stock History"
                        actions={
                            <Badge tone="info">{stockings.total} total</Badge>
                        }
                    >
                        <DataTable
                            columns={columns}
                            rows={stockings.data}
                            empty={
                                <div className="px-6 py-12 text-center">
                                    <h4 className="text-sm font-semibold text-text">
                                        No stock history available.
                                    </h4>
                                    <p className="mt-1 text-sm text-muted">
                                        No stocking records exist yet. Add stock
                                        to a pond to build its history.
                                    </p>
                                </div>
                            }
                        />
                        <Pagination paginator={stockings} />
                    </Card>
                </div>
            </div>
        </>
    );
}

export default withLayout(LedgerStocking, "Stocking");
