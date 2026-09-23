import React from "react";
import { useForm } from "@inertiajs/react";
import { Field, Input, Textarea } from "../../../Components/Form";
import Button from "../../../Components/Button";

/** SpeciesForm — shared create/edit form (React mirror of fish/species/_form). */
export default function SpeciesForm({
    species = null,
    action,
    method = "post",
}) {
    const { data, setData, post, put, processing, errors, transform } = useForm(
        {
            name: species?.name ?? "",
            local_name: species?.local_name ?? "",
            scientific_name: species?.scientific_name ?? "",
            default_price_per_kg: species?.default_price_per_kg ?? "",
            description: species?.description ?? "",
            is_active: species ? !!species.is_active : true,
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
                    label="Species name"
                    name="name"
                    required
                    hint="Unique, e.g. Rohu, Tilapia, Catla."
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
                    label="Local name"
                    name="local_name"
                    hint="Optional Bengali/Bangla name."
                    error={errors.local_name}
                >
                    <Input
                        name="local_name"
                        value={data.local_name}
                        onChange={(e) => setData("local_name", e.target.value)}
                    />
                </Field>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field
                    label="Scientific name"
                    name="scientific_name"
                    hint="Optional. e.g. Labeo rohita."
                    error={errors.scientific_name}
                >
                    <Input
                        name="scientific_name"
                        value={data.scientific_name}
                        onChange={(e) =>
                            setData("scientific_name", e.target.value)
                        }
                    />
                </Field>

                <Field
                    label="Default price per kg"
                    name="default_price_per_kg"
                    hint="Optional. Used as the default when selling."
                    error={errors.default_price_per_kg}
                >
                    <Input
                        name="default_price_per_kg"
                        type="number"
                        step="0.01"
                        min="0"
                        inputMode="decimal"
                        value={data.default_price_per_kg}
                        onChange={(e) =>
                            setData("default_price_per_kg", e.target.value)
                        }
                    />
                </Field>
            </div>

            <Field
                label="Description"
                name="description"
                hint="Optional notes about this species."
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
                        Active — offered when recording stockings and harvests
                    </span>
                </label>
            </Field>

            <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {method === "put" ? "Save changes" : "Create species"}
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
