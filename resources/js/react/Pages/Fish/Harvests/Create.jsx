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

/** Record Harvest — fish removed from a pond (stock OUT). */
function HarvestsCreate({ options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const { data, setData, post, processing, errors } = useForm({
        pond_id: "",
        fish_species_id: "",
        quantity: "",
        total_weight_kg: "",
        harvested_on: defaultDate,
        destination: "",
        note: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["fish.harvests.store"]);
    };

    const hasSpecies = Object.keys(options.speciesOptions || {}).length > 0;
    const hasPonds = Object.keys(options.pondOptions || {}).length > 0;

    return (
        <>
            <Head title="Record Harvest" />
            <PageHeader
                title="Record Harvest"
                subtitle="Record fish removed from a pond. The pond's live stock is reduced by the quantity entered."
                breadcrumb={[
                    { label: "Fish Stock" },
                    { label: "Harvest", href: routes["fish.harvests.index"] },
                    { label: "Record Harvest" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                {!hasSpecies ? (
                    <Card
                        tone="warning"
                        title="No active fish species is available"
                    >
                        <p className="text-sm text-text-soft">
                            A harvest must name a species, so add one first.
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
                            Create a pond before recording a harvest.
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
                    <>
                        <div className="surface-card border border-info/30 p-4 text-sm text-text-soft">
                            A harvest can never take a pond's stock below zero.
                            Live stock per pond:
                            <ul className="mt-2 space-y-0.5 text-xs">
                                {Object.entries(options.pondOptions || {}).map(
                                    ([id, label]) => (
                                        <li key={id}>
                                            {label}:{" "}
                                            <strong>
                                                {Number(
                                                    options.stockByPond?.[id] ??
                                                        0,
                                                ).toLocaleString()}
                                            </strong>{" "}
                                            fish
                                        </li>
                                    ),
                                )}
                            </ul>
                        </div>

                        <form onSubmit={submit} className="mt-4">
                            <Card
                                title="Harvest details"
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
                                                options={
                                                    options.pondOptions || {}
                                                }
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

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <Field
                                            label="Quantity"
                                            name="quantity"
                                            required
                                            hint="Whole number of fish. Cannot exceed live stock."
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
                                            label="Total weight (kg)"
                                            name="total_weight_kg"
                                            hint="Optional. Average weight is derived."
                                            error={errors.total_weight_kg}
                                        >
                                            <Input
                                                name="total_weight_kg"
                                                type="number"
                                                step="0.001"
                                                min="0.001"
                                                inputMode="decimal"
                                                value={data.total_weight_kg}
                                                onChange={(e) =>
                                                    setData(
                                                        "total_weight_kg",
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <Field
                                            label="Harvest date"
                                            name="harvested_on"
                                            required
                                            error={errors.harvested_on}
                                        >
                                            <DatePicker
                                                name="harvested_on"
                                                value={data.harvested_on}
                                                onChange={(e) =>
                                                    setData(
                                                        "harvested_on",
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </Field>

                                        <Field
                                            label="Destination"
                                            name="destination"
                                            hint="Optional. e.g. Market, cold storage, a customer."
                                            error={errors.destination}
                                        >
                                            <Input
                                                name="destination"
                                                value={data.destination}
                                                onChange={(e) =>
                                                    setData(
                                                        "destination",
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
                                    Record harvest
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
                    </>
                )}
            </div>
        </>
    );
}

export default withLayout(HarvestsCreate, "Record Harvest");
