import React from "react";
import { useForm } from "@inertiajs/react";
import { Field, Input, Textarea } from "../../../Components/Form";
import Button from "../../../Components/Button";

/**
 * PondTypeForm — the shared create/edit form for pond types.
 *
 * Laravel remains authoritative for validation. `is_active` is a boolean; the
 * checkbox submits 1 when ticked, and the hidden companion field is handled by
 * Inertia's transform (we send 0 when unticked).
 */
export default function PondTypeForm({ type = null, action, method = "post" }) {
    const { data, setData, post, put, processing, errors, transform } = useForm(
        {
            name: type?.name ?? "",
            description: type?.description ?? "",
            is_active: type ? !!type.is_active : true,
        },
    );

    const submit = (e) => {
        e.preventDefault();
        // Send the boolean as 1/0 so the server's boolean rule sees a real value.
        transform((d) => ({ ...d, is_active: d.is_active ? 1 : 0 }));
        if (method === "put") {
            put(action);
        } else {
            post(action);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <Field
                label="Type name"
                name="name"
                required
                hint="Unique, e.g. Grow Out Pond, Nursery Pond, Brood Pond."
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
                label="Description"
                name="description"
                hint="Optional. Explains what this classification is used for."
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
                        Active — offered when creating or editing a pond
                    </span>
                </label>
            </Field>

            <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {method === "put" ? "Save changes" : "Create pond type"}
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
