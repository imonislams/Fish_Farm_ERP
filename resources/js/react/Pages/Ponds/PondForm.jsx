import React from "react";
import { useForm } from "@inertiajs/react";
import { Field, Input, Select, Textarea } from "../../Components/Form";
import Button from "../../Components/Button";

/**
 * PondForm — the shared create/edit form (React mirror of ponds/_form.blade.php).
 *
 * Laravel remains authoritative for validation: `errors` come straight from the
 * FormRequest and are rendered per field. `is_active` is NOT a form field — the
 * server derives it from `status`, so the two can never contradict.
 */
export default function PondForm({
    pond = null,
    options = {},
    action,
    method = "post",
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        pond_number: pond?.pond_number ?? "",
        name: pond?.name ?? "",
        pond_type_id: pond?.pond_type_id ?? "",
        size: pond?.size ?? "",
        size_unit: pond?.size_unit ?? "decimal",
        depth: pond?.depth ?? "",
        depth_unit: pond?.depth_unit ?? "",
        location: pond?.location ?? "",
        water_source: pond?.water_source ?? "",
        status: pond?.status ?? "active",
        description: pond?.description ?? "",
    });

    const submit = (e) => {
        e.preventDefault();
        if (method === "put") {
            put(action);
        } else {
            post(action);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field
                    label="Pond number"
                    name="pond_number"
                    required
                    hint="Unique operating code, e.g. P-01."
                    error={errors.pond_number}
                >
                    <Input
                        name="pond_number"
                        value={data.pond_number}
                        onChange={(e) => setData("pond_number", e.target.value)}
                        autoFocus
                    />
                </Field>

                <Field
                    label="Pond name"
                    name="name"
                    required
                    error={errors.name}
                >
                    <Input
                        name="name"
                        value={data.name}
                        onChange={(e) => setData("name", e.target.value)}
                    />
                </Field>
            </div>

            <Field
                label="Pond type"
                name="pond_type_id"
                required
                hint="Classification of the pond. Managed under Pond Types."
                error={errors.pond_type_id}
            >
                <Select
                    name="pond_type_id"
                    value={data.pond_type_id}
                    onChange={(e) => setData("pond_type_id", e.target.value)}
                    placeholder="Select a pond type…"
                    options={options.types || {}}
                />
            </Field>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Field
                    label="Size"
                    name="size"
                    required
                    hint="Greater than zero. Up to 3 decimals."
                    error={errors.size}
                >
                    <Input
                        name="size"
                        type="number"
                        step="0.001"
                        min="0.001"
                        inputMode="decimal"
                        value={data.size}
                        onChange={(e) => setData("size", e.target.value)}
                    />
                </Field>

                <Field
                    label="Size unit"
                    name="size_unit"
                    required
                    error={errors.size_unit}
                >
                    <Select
                        name="size_unit"
                        value={data.size_unit}
                        onChange={(e) => setData("size_unit", e.target.value)}
                        options={options.sizeUnits || {}}
                    />
                </Field>

                <div className="hidden sm:block" />

                <Field
                    label="Depth"
                    name="depth"
                    hint="Optional. Leave blank if not recorded."
                    error={errors.depth}
                >
                    <Input
                        name="depth"
                        type="number"
                        step="0.001"
                        min="0.001"
                        inputMode="decimal"
                        value={data.depth}
                        onChange={(e) => setData("depth", e.target.value)}
                    />
                </Field>

                <Field
                    label="Depth unit"
                    name="depth_unit"
                    hint="Required when a depth is given."
                    error={errors.depth_unit}
                >
                    <Select
                        name="depth_unit"
                        value={data.depth_unit}
                        onChange={(e) => setData("depth_unit", e.target.value)}
                        placeholder="Not recorded"
                        options={options.depthUnits || {}}
                    />
                </Field>

                <div className="hidden sm:block" />
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field
                    label="Location"
                    name="location"
                    hint="Where the pond sits on the farm."
                    error={errors.location}
                >
                    <Input
                        name="location"
                        value={data.location}
                        onChange={(e) => setData("location", e.target.value)}
                    />
                </Field>

                <Field
                    label="Water source"
                    name="water_source"
                    hint="e.g. Canal, tube well, river."
                    error={errors.water_source}
                >
                    <Input
                        name="water_source"
                        value={data.water_source}
                        onChange={(e) =>
                            setData("water_source", e.target.value)
                        }
                    />
                </Field>
            </div>

            <Field
                label="Status"
                name="status"
                required
                hint="Maintenance and inactive ponds cannot be stocked."
                error={errors.status}
            >
                <Select
                    name="status"
                    value={data.status}
                    onChange={(e) => setData("status", e.target.value)}
                    options={options.statuses || {}}
                />
            </Field>

            <Field
                label="Description"
                name="description"
                hint="Optional notes about this pond."
                error={errors.description}
            >
                <Textarea
                    name="description"
                    rows={4}
                    value={data.description}
                    onChange={(e) => setData("description", e.target.value)}
                />
            </Field>

            <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {method === "put" ? "Save changes" : "Create pond"}
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
