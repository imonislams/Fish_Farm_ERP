import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import Button from "../../../Components/Button";
import {
    Field,
    Input,
    Select,
    DatePicker,
    Textarea,
} from "../../../Components/Form";

/** Record Stocking — fish placed into a pond (stock IN). */
function StockingsCreate({ options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const { data, setData, post, processing, errors } = useForm({
        pond_id: "",
        fish_species_id: "",
        quantity: "",
        avg_weight_g: "",
        unit_cost: "",
        stocked_on: defaultDate,
        supplier_name: "",
        note: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["fish.stockings.store"]);
    };

    const hasSpecies = Object.keys(options.speciesOptions || {}).length > 0;
    const hasPonds = Object.keys(options.pondOptions || {}).length > 0;

    return (
        <>
            <Head title="Record Stocking" />
            <PageHeader
                title="Record Stocking"
                subtitle="Record fish placed into a pond. Stock is increased by the quantity entered."
                breadcrumb={[
                    { label: "Fish Stock" },
                    {
                        label: "Stock In / Stocking",
                        href: routes["fish.stockings.index"],
                    },
                    { label: "Record Stocking" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                {!hasSpecies ? (
                    <Card
                        tone="warning"
                        title="No active fish species is available"
                    >
                        <p className="text-sm text-text-soft">
                            A stocking must name a species, so add one first.
                        </p>
                        {can("fish.species.manage") &&
                            routes["fish.species.create"] && (
                                <div className="mt-3">
                                    <Button
                                        href={routes["fish.species.create"]}
                                        variant="primary"
                                        size="sm"
                                    >
                                        New Species
                                    </Button>
                                </div>
                            )}
                    </Card>
                ) : !hasPonds ? (
                    <Card tone="warning" title="No ponds exist yet">
                        <p className="text-sm text-text-soft">
                            Create a pond before recording a stocking.
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
                            title="Stocking details"
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
                                            options={
                                                options.speciesOptions || {}
                                            }
                                        />
                                    </Field>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <Field
                                        label="Quantity"
                                        name="quantity"
                                        required
                                        hint="Whole number of fish."
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
                                                setData(
                                                    "quantity",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Average weight (g)"
                                        name="avg_weight_g"
                                        hint="Optional. Total weight is derived."
                                        error={errors.avg_weight_g}
                                    >
                                        <Input
                                            name="avg_weight_g"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            inputMode="decimal"
                                            value={data.avg_weight_g}
                                            onChange={(e) =>
                                                setData(
                                                    "avg_weight_g",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Unit cost"
                                        name="unit_cost"
                                        hint="Optional cost per fish."
                                        error={errors.unit_cost}
                                    >
                                        <Input
                                            name="unit_cost"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            inputMode="decimal"
                                            value={data.unit_cost}
                                            onChange={(e) =>
                                                setData(
                                                    "unit_cost",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field
                                        label="Stocking date"
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

                                    <Field
                                        label="Supplier"
                                        name="supplier_name"
                                        hint="Optional. Where the fish came from."
                                        error={errors.supplier_name}
                                    >
                                        <Input
                                            name="supplier_name"
                                            value={data.supplier_name}
                                            onChange={(e) =>
                                                setData(
                                                    "supplier_name",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>

                                <Field
                                    label="Note"
                                    name="note"
                                    error={errors.note}
                                >
                                    <Textarea
                                        name="note"
                                        rows={3}
                                        value={data.note}
                                        onChange={(e) =>
                                            setData("note", e.target.value)
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
                                Record stocking
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

export default withLayout(StockingsCreate, "Record Stocking");
