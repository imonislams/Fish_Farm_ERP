import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout, date, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import { DataTable, Pagination } from "../../Components/DataTable";
import Button from "../../Components/Button";
import { Field, Input, Select, DatePicker } from "../../Components/Form";

/** Pond Ledger — Death (Mortality): New death form + Death History. */
function LedgerMortality({ mortalities, options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const { data, setData, post, processing, errors, reset } = useForm({
        pond_id: "",
        quantity: "",
        cause: "",
        recorded_on: defaultDate,
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["ledger.mortality.store"], {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const hasPonds = Object.keys(options.pondOptions || {}).length > 0;

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
            key: "quantity",
            label: "Number",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap font-medium text-danger">
                    {r.quantity}
                </span>
            ),
        },
        {
            key: "cause",
            label: "Causes",
            render: (r) => <span className="text-text-soft">{r.cause}</span>,
        },
    ];

    return (
        <>
            <Head title="Death (Mortality)" />

            <PageHeader
                title="Death (Mortality)"
                subtitle="Fish mortality records"
                breadcrumb={[
                    { label: "Pond Ledger", href: routes["ledger.index"] },
                    { label: "Death (Mortality)" },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-1">
                    <Card
                        title="New death records"
                        subtitle="Record fish mortality."
                    >
                        {!hasPonds ? (
                            <div className="surface-card border-warning/40 p-3 text-sm text-text-soft">
                                <strong className="text-text">
                                    No ponds exist yet.
                                </strong>{" "}
                                Create a pond first.
                            </div>
                        ) : can("pond_ledger.mortality.create") ? (
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
                                    label="Number"
                                    name="quantity"
                                    required
                                    hint="Cannot exceed the pond's live stock."
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
                                    label="Cause"
                                    name="cause"
                                    error={errors.cause}
                                >
                                    <Select
                                        name="cause"
                                        value={data.cause}
                                        onChange={(e) =>
                                            setData("cause", e.target.value)
                                        }
                                        placeholder="Select a cause…"
                                        options={options.causeOptions || {}}
                                    />
                                </Field>

                                <Field
                                    label="Date"
                                    name="recorded_on"
                                    required
                                    error={errors.recorded_on}
                                >
                                    <DatePicker
                                        name="recorded_on"
                                        value={data.recorded_on}
                                        onChange={(e) =>
                                            setData(
                                                "recorded_on",
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
                                You have view-only access to the mortality
                                records.
                            </div>
                        )}
                    </Card>
                </div>

                <div className="lg:col-span-2">
                    <Card
                        padded={false}
                        title="Death History"
                        actions={
                            <Badge tone="info">{mortalities.total} total</Badge>
                        }
                    >
                        <DataTable
                            columns={columns}
                            rows={mortalities.data}
                            empty={
                                <div className="px-6 py-12 text-center">
                                    <h4 className="text-sm font-semibold text-text">
                                        No mortality records found.
                                    </h4>
                                    <p className="mt-1 text-sm text-muted">
                                        No fish mortality has been recorded yet.
                                    </p>
                                </div>
                            }
                        />
                        <Pagination paginator={mortalities} />
                    </Card>
                </div>
            </div>
        </>
    );
}

export default withLayout(LedgerMortality, "Death (Mortality)");
