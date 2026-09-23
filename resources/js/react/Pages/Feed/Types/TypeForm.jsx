import React from "react";
import { useForm } from "@inertiajs/react";
import { Field, Input, Textarea } from "../../../Components/Form";
import Button from "../../../Components/Button";

/** FeedTypeForm — shared create/edit form (React mirror of feed/types/_form). */
export default function FeedTypeForm({ type = null, action, method = "post" }) {
    const { data, setData, post, put, processing, errors, transform } = useForm(
        {
            name: type?.name ?? "",
            brand: type?.brand ?? "",
            protein_percent: type?.protein_percent ?? "",
            package_weight_kg: type?.package_weight_kg ?? "",
            unit: type?.unit ?? "",
            default_unit_cost: type?.default_unit_cost ?? "",
            low_stock_level_kg: type?.low_stock_level_kg ?? "",
            critical_stock_level_kg: type?.critical_stock_level_kg ?? "",
            description: type?.description ?? "",
            is_active: type ? !!type.is_active : true,
        },
    );

    const submit = (e) => {
        e.preventDefault();
        transform((d) => ({ ...d, is_active: d.is_active ? 1 : 0 }));
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
                    label="Feed name"
                    name="name"
                    required
                    hint="Unique, e.g. Grower Pellet, Starter Feed."
                    error={errors.name}
                >
                    <Input
                        name="name"
                        value={data.name}
                        onChange={(e) => setData("name", e.target.value)}
                        autoFocus
                    />
                </Field>

                <Field
                    label="Brand"
                    name="brand"
                    hint="Optional. The manufacturer."
                    error={errors.brand}
                >
                    <Input
                        name="brand"
                        value={data.brand}
                        onChange={(e) => setData("brand", e.target.value)}
                    />
                </Field>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Field
                    label="Protein (%)"
                    name="protein_percent"
                    hint="Optional. 0–100."
                    error={errors.protein_percent}
                >
                    <Input
                        name="protein_percent"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        inputMode="decimal"
                        value={data.protein_percent}
                        onChange={(e) =>
                            setData("protein_percent", e.target.value)
                        }
                    />
                </Field>

                <Field
                    label="Package weight (kg)"
                    name="package_weight_kg"
                    hint="Optional. Weight of one package."
                    error={errors.package_weight_kg}
                >
                    <Input
                        name="package_weight_kg"
                        type="number"
                        step="0.001"
                        min="0.001"
                        inputMode="decimal"
                        value={data.package_weight_kg}
                        onChange={(e) =>
                            setData("package_weight_kg", e.target.value)
                        }
                    />
                </Field>

                <Field
                    label="Unit"
                    name="unit"
                    hint="Optional. e.g. kg bag."
                    error={errors.unit}
                >
                    <Input
                        name="unit"
                        value={data.unit}
                        onChange={(e) => setData("unit", e.target.value)}
                    />
                </Field>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field
                    label="Default unit cost"
                    name="default_unit_cost"
                    hint="Optional. Used as the default when purchasing."
                    error={errors.default_unit_cost}
                >
                    <Input
                        name="default_unit_cost"
                        type="number"
                        step="0.01"
                        min="0"
                        inputMode="decimal"
                        value={data.default_unit_cost}
                        onChange={(e) =>
                            setData("default_unit_cost", e.target.value)
                        }
                    />
                </Field>

                <Field
                    label="Low stock level (kg)"
                    name="low_stock_level_kg"
                    hint="Optional. Stock at or below this reads as low."
                    error={errors.low_stock_level_kg}
                >
                    <Input
                        name="low_stock_level_kg"
                        type="number"
                        step="0.001"
                        min="0"
                        inputMode="decimal"
                        value={data.low_stock_level_kg}
                        onChange={(e) =>
                            setData("low_stock_level_kg", e.target.value)
                        }
                    />
                </Field>

                <Field
                    label="Critical stock level (kg)"
                    name="critical_stock_level_kg"
                    hint="Optional. A stricter level — stock at or below this raises a critical alert."
                    error={errors.critical_stock_level_kg}
                >
                    <Input
                        name="critical_stock_level_kg"
                        type="number"
                        step="0.001"
                        min="0"
                        inputMode="decimal"
                        value={data.critical_stock_level_kg}
                        onChange={(e) =>
                            setData("critical_stock_level_kg", e.target.value)
                        }
                    />
                </Field>
            </div>

            <Field
                label="Description"
                name="description"
                hint="Optional notes about this feed."
                error={errors.description}
            >
                <Textarea
                    name="description"
                    rows={3}
                    value={data.description}
                    onChange={(e) => setData("description", e.target.value)}
                />
            </Field>

            <Field label="Status" name="is_active" error={errors.is_active}>
                <label className="flex items-center gap-2 text-sm text-text-soft">
                    <input
                        type="checkbox"
                        checked={data.is_active}
                        onChange={(e) => setData("is_active", e.target.checked)}
                        className="h-4 w-4 rounded border-border-strong text-primary focus:ring-primary/40"
                    />
                    <span>
                        Active — offered when recording purchases, usage and
                        adjustments
                    </span>
                </label>
            </Field>

            <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {method === "put" ? "Save changes" : "Create feed type"}
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
