import React from "react";
import { useForm, usePage } from "@inertiajs/react";
import {
    Field,
    Input,
    Select,
    DatePicker,
    Textarea,
} from "../../../Components/Form";
import Button from "../../../Components/Button";
import { Card } from "../../../Components/Card";

/**
 * BatchForm — shared by create and edit.
 *
 * On CREATE, an initial quantity (with a weight) is recorded as a REAL stocking
 * movement, so the batch's first movement is a genuine stock row. On EDIT only the
 * header (status / end date / note) is editable — the quantity lives in the records.
 */
export default function BatchForm({
    batch = {},
    options = {},
    mode = "create",
    defaultDate = "",
}) {
    const { props } = usePage();
    const routes = props.routes || {};

    const isEdit = mode === "edit";

    const { data, setData, post, put, processing, errors } = useForm({
        pond_id: batch.pond_id ?? "",
        fish_species_id: batch.fish_species_id ?? "",
        code: batch.code ?? "",
        started_on: batch.started_on ?? defaultDate,
        initial_quantity: batch.initial_quantity ?? "",
        avg_weight_g: "",
        unit_cost: "",
        supplier_name: "",
        status: batch.status ?? "active",
        ended_on: batch.ended_on ?? "",
        note: batch.note ?? "",
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(routes["fish.batches.update"].replace("{batch}", batch.id));
        } else {
            post(routes["fish.batches.store"]);
        }
    };

    return (
        <form onSubmit={submit}>
            <Card
                title={isEdit ? "Batch details" : "New batch"}
                subtitle="Fields marked with * are required."
            >
                {!isEdit && (
                    <div className="mb-5 flex items-start gap-2 rounded-control border border-info/30 bg-info/5 p-3 text-sm text-text-soft">
                        <span className="mt-0.5">ℹ</span>
                        <p>
                            The initial quantity is recorded as a real stocking
                            movement, so the batch and the fish-stock ledger
                            stay the same source of truth.
                        </p>
                    </div>
                )}

                <div className="space-y-5">
                    {isEdit ? (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <p className="text-xs uppercase tracking-wide text-muted">
                                    Pond
                                </p>
                                <p className="mt-1 text-sm text-text">
                                    {batch.pond}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs uppercase tracking-wide text-muted">
                                    Species
                                </p>
                                <p className="mt-1 text-sm text-text">
                                    {batch.species}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs uppercase tracking-wide text-muted">
                                    Current quantity
                                </p>
                                <p className="mt-1 text-sm text-text">
                                    {batch.current_quantity}
                                </p>
                            </div>
                        </div>
                    ) : (
                        <>
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
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Field
                                    label="Started on"
                                    name="started_on"
                                    required
                                    error={errors.started_on}
                                >
                                    <DatePicker
                                        name="started_on"
                                        value={data.started_on}
                                        onChange={(e) =>
                                            setData(
                                                "started_on",
                                                e.target.value,
                                            )
                                        }
                                    />
                                </Field>

                                <Field
                                    label="Batch code"
                                    name="code"
                                    hint="Leave blank to auto-generate."
                                    error={errors.code}
                                >
                                    <Input
                                        name="code"
                                        value={data.code}
                                        onChange={(e) =>
                                            setData("code", e.target.value)
                                        }
                                        placeholder="e.g. BATCH-0001"
                                    />
                                </Field>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <Field
                                    label="Initial quantity"
                                    name="initial_quantity"
                                    hint="Fish put in at the start."
                                    error={errors.initial_quantity}
                                >
                                    <Input
                                        name="initial_quantity"
                                        type="number"
                                        step="1"
                                        min="0"
                                        inputMode="numeric"
                                        value={data.initial_quantity}
                                        onChange={(e) =>
                                            setData(
                                                "initial_quantity",
                                                e.target.value,
                                            )
                                        }
                                    />
                                </Field>

                                <Field
                                    label="Avg weight (g)"
                                    name="avg_weight_g"
                                    error={errors.avg_weight_g}
                                >
                                    <Input
                                        name="avg_weight_g"
                                        type="number"
                                        step="0.01"
                                        min="0"
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
                                            setData("unit_cost", e.target.value)
                                        }
                                    />
                                </Field>
                            </div>

                            <Field
                                label="Supplier"
                                name="supplier_name"
                                error={errors.supplier_name}
                            >
                                <Input
                                    name="supplier_name"
                                    value={data.supplier_name}
                                    onChange={(e) =>
                                        setData("supplier_name", e.target.value)
                                    }
                                    placeholder="Optional"
                                />
                            </Field>
                        </>
                    )}

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <Field
                            label="Status"
                            name="status"
                            required
                            error={errors.status}
                        >
                            <Select
                                name="status"
                                value={data.status}
                                onChange={(e) =>
                                    setData("status", e.target.value)
                                }
                                options={options.statusOptions || {}}
                            />
                        </Field>

                        {isEdit && (
                            <Field
                                label="Ended on"
                                name="ended_on"
                                hint="Set automatically when the cycle ends."
                                error={errors.ended_on}
                            >
                                <DatePicker
                                    name="ended_on"
                                    value={data.ended_on}
                                    onChange={(e) =>
                                        setData("ended_on", e.target.value)
                                    }
                                />
                            </Field>
                        )}
                    </div>

                    <Field label="Note" name="note" error={errors.note}>
                        <Textarea
                            name="note"
                            rows={3}
                            value={data.note}
                            onChange={(e) => setData("note", e.target.value)}
                        />
                    </Field>
                </div>
            </Card>

            <div className="mt-4 flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {isEdit ? "Save changes" : "Create batch"}
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
    );
}
