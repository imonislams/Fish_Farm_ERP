import React from "react";
import { useForm } from "@inertiajs/react";
import { Field, Input, Select } from "../../../Components/Form";
import Button from "../../../Components/Button";

/** UserForm — shared create/edit form (React mirror of settings/users/_form). */
export default function UserForm({
    user = null,
    roles = {},
    action,
    method = "post",
    isSelf = false,
}) {
    const { data, setData, post, put, processing, errors, transform } = useForm(
        {
            name: user?.name ?? "",
            email: user?.email ?? "",
            role_id: user?.role_id ?? "",
            password: "",
            password_confirmation: "",
            is_active: user ? !!user.is_active : true,
        },
    );

    const isEdit = method === "put";

    const submit = (e) => {
        e.preventDefault();
        transform((d) => ({ ...d, is_active: d.is_active ? 1 : 0 }));
        if (isEdit) {
            put(action);
        } else {
            post(action);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <Field label="Full name" name="name" required error={errors.name}>
                <Input
                    name="name"
                    value={data.name}
                    onChange={(e) => setData("name", e.target.value)}
                    autoFocus
                />
            </Field>

            <Field
                label="Email address"
                name="email"
                required
                hint="Used to sign in. Must be unique."
                error={errors.email}
            >
                <Input
                    name="email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData("email", e.target.value)}
                />
            </Field>

            <Field
                label="Role"
                name="role_id"
                required
                hint="Determines which parts of the system this user can access."
                error={errors.role_id}
            >
                <Select
                    name="role_id"
                    value={data.role_id}
                    onChange={(e) => setData("role_id", e.target.value)}
                    placeholder="Select a role…"
                    options={roles}
                />
            </Field>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field
                    label="Password"
                    name="password"
                    required={!isEdit}
                    hint={
                        isEdit
                            ? "Leave blank to keep the current password."
                            : "Minimum 8 characters."
                    }
                    error={errors.password}
                >
                    <Input
                        name="password"
                        type="password"
                        autoComplete="new-password"
                        value={data.password}
                        onChange={(e) => setData("password", e.target.value)}
                    />
                </Field>

                <Field
                    label="Confirm password"
                    name="password_confirmation"
                    required={!isEdit}
                    error={errors.password_confirmation}
                >
                    <Input
                        name="password_confirmation"
                        type="password"
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) =>
                            setData("password_confirmation", e.target.value)
                        }
                    />
                </Field>
            </div>

            <Field
                label="Account status"
                name="is_active"
                error={errors.is_active}
            >
                <label className="flex items-center gap-2 text-sm text-text-soft">
                    <input
                        type="checkbox"
                        checked={data.is_active}
                        disabled={isEdit && isSelf}
                        onChange={(e) => setData("is_active", e.target.checked)}
                        className="h-4 w-4 rounded border-border-strong text-primary focus:ring-primary/40"
                    />
                    <span>Active — the user can sign in</span>
                </label>
                {isEdit && isSelf && (
                    <p className="text-xs text-muted">
                        You cannot deactivate your own account.
                    </p>
                )}
            </Field>

            <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {isEdit ? "Save changes" : "Create user"}
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
