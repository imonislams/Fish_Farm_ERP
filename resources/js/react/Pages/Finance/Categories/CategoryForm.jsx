import React from "react";
import { useForm } from "@inertiajs/react";
import { Field, Input } from "../../../Components/Form";
import Button from "../../../Components/Button";

/** CategoryForm — shared create/edit form for expense categories. */
export default function CategoryForm({
    category = null,
    action,
    method = "post",
}) {
    const { data, setData, post, put, processing, errors, transform } = useForm(
        {
            name: category?.name ?? "",
            description: category?.description ?? "",
            is_active: category ? !!category.is_active : true,
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
            <Field
                label="Category name"
                name="name"
                required
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
                error={errors.description}
            >
                <Input
                    name="description"
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
                    <span>Active — offered when recording an expense</span>
                </label>
            </Field>

            <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {method === "put" ? "Save changes" : "Create category"}
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
