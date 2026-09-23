import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card } from "../../Components/Card";
import Button from "../../Components/Button";
import {
    Field,
    Input,
    Select,
    DatePicker,
    Textarea,
} from "../../Components/Form";

/**
 * New Ledger Entry — record income or an expense against a pond.
 *
 * The category list shown depends on the chosen type; React state swaps it. The
 * server re-validates the type/category pair, so this is convenience only.
 */
function LedgerCreate({
    options = {},
    categoriesByType = {},
    defaultDate = "",
    selectedPondId = null,
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const { data, setData, post, processing, errors } = useForm({
        pond_id: selectedPondId ?? "",
        entry_type: "debit",
        category: "",
        amount: "",
        entry_date: defaultDate,
        reference: "",
        description: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["ledger.transactions.store"]);
    };

    const hasPonds = Object.keys(options.pondOptions || {}).length > 0;
    const categories = categoriesByType[data.entry_type] || {};

    return (
        <>
            <Head title="New Ledger Entry" />
            <PageHeader
                title="New Ledger Entry"
                subtitle="Record income or an expense against a pond."
                breadcrumb={[
                    { label: "Pond Ledger" },
                    {
                        label: "Pond Transactions",
                        href: routes["ledger.transactions"],
                    },
                    { label: "New Entry" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                {!hasPonds ? (
                    <Card tone="warning" title="No ponds exist yet">
                        <p className="text-sm text-text-soft">
                            A ledger entry must be attributed to a pond, so
                            create one first.
                        </p>
                        {can("pond.create") && routes["ponds.create"] && (
                            <div className="mt-3">
                                <Button
                                    href={routes["ponds.create"]}
                                    variant="primary"
                                    size="sm"
                                >
                                    New Pond
                                </Button>
                            </div>
                        )}
                    </Card>
                ) : (
                    <form onSubmit={submit}>
                        <Card
                            title="Entry details"
                            subtitle="Fields marked with * are required."
                        >
                            <div className="space-y-5">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                                                setData(
                                                    "pond_id",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Select a pond…"
                                            options={options.pondOptions || {}}
                                        />
                                    </Field>

                                    <Field
                                        label="Type"
                                        name="entry_type"
                                        required
                                        hint="Income increases the pond's profit; an expense reduces it."
                                        error={errors.entry_type}
                                    >
                                        <Select
                                            name="entry_type"
                                            value={data.entry_type}
                                            onChange={(e) => {
                                                setData(
                                                    "entry_type",
                                                    e.target.value,
                                                );
                                                // Reset category: the list is per type.
                                                setData("category", "");
                                            }}
                                            options={options.typeOptions || {}}
                                        />
                                    </Field>
                                </div>

                                <Field
                                    label="Category"
                                    name="category"
                                    required
                                    error={errors.category}
                                >
                                    <Select
                                        name="category"
                                        value={data.category}
                                        onChange={(e) =>
                                            setData("category", e.target.value)
                                        }
                                        placeholder="Select a category…"
                                        options={categories}
                                    />
                                </Field>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field
                                        label="Amount"
                                        name="amount"
                                        required
                                        hint="A positive amount. The type decides its direction."
                                        error={errors.amount}
                                    >
                                        <Input
                                            name="amount"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            inputMode="decimal"
                                            value={data.amount}
                                            onChange={(e) =>
                                                setData(
                                                    "amount",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Date"
                                        name="entry_date"
                                        required
                                        error={errors.entry_date}
                                    >
                                        <DatePicker
                                            name="entry_date"
                                            value={data.entry_date}
                                            onChange={(e) =>
                                                setData(
                                                    "entry_date",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>

                                <Field
                                    label="Reference"
                                    name="reference"
                                    hint="Optional. An invoice, receipt or note number."
                                    error={errors.reference}
                                >
                                    <Input
                                        name="reference"
                                        value={data.reference}
                                        onChange={(e) =>
                                            setData("reference", e.target.value)
                                        }
                                    />
                                </Field>

                                <Field
                                    label="Description"
                                    name="description"
                                    hint="Optional extra detail."
                                    error={errors.description}
                                >
                                    <Textarea
                                        name="description"
                                        rows={3}
                                        value={data.description}
                                        onChange={(e) =>
                                            setData(
                                                "description",
                                                e.target.value,
                                            )
                                        }
                                    />
                                </Field>
                            </div>
                        </Card>

                        <div className="mt-4 flex flex-wrap items-center gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={processing}
                            >
                                Record entry
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => window.history.back()}
                                disabled={processing}
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </>
    );
}

export default withLayout(LedgerCreate, "New Ledger Entry");
