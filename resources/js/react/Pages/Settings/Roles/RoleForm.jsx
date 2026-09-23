import React from "react";
import { useForm } from "@inertiajs/react";
import { Field, Input, Textarea } from "../../../Components/Form";
import Button from "../../../Components/Button";

/**
 * RoleForm — create/edit a role and its permission matrix.
 *
 * The matrix is grouped by module (from the database). "Select all" toggles a
 * whole group. Laravel still validates the permission names server-side.
 */
export default function RoleForm({
    role = null,
    groups = [],
    action,
    method = "post",
}) {
    const isEdit = method === "put";

    const { data, setData, post, put, processing, errors } = useForm({
        label: role?.label ?? "",
        name: role?.name ?? "",
        description: role?.description ?? "",
        permissions: role?.selected ?? [],
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(action);
        } else {
            post(action);
        }
    };

    const toggle = (name) =>
        setData(
            "permissions",
            data.permissions.includes(name)
                ? data.permissions.filter((p) => p !== name)
                : [...data.permissions, name],
        );

    const toggleGroup = (names, allSelected) =>
        setData(
            "permissions",
            allSelected
                ? data.permissions.filter((p) => !names.includes(p))
                : [...new Set([...data.permissions, ...names])],
        );

    return (
        <form onSubmit={submit} className="mt-5 space-y-4">
            <div className="surface-card overflow-hidden">
                <header className="border-b border-border px-4 py-3">
                    <h3 className="text-sm font-semibold text-text">
                        Role details
                    </h3>
                </header>
                <div className="space-y-4 p-4">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <Field
                            label="Display label"
                            name="label"
                            required
                            hint="Shown in the interface, e.g. “Pond Supervisor”."
                            error={errors.label}
                        >
                            <Input
                                name="label"
                                value={data.label}
                                onChange={(e) =>
                                    setData("label", e.target.value)
                                }
                                autoFocus
                            />
                        </Field>

                        <Field
                            label="Machine name"
                            name="name"
                            required
                            hint={
                                role?.is_system
                                    ? "System role — the machine name cannot be changed."
                                    : "Lowercase identifier used by the code, e.g. pond_supervisor."
                            }
                            error={errors.name}
                        >
                            <Input
                                name="name"
                                value={data.name}
                                disabled={role?.is_system}
                                onChange={(e) =>
                                    setData("name", e.target.value)
                                }
                                placeholder="pond_supervisor"
                            />
                        </Field>
                    </div>

                    <Field
                        label="Description"
                        name="description"
                        hint="Optional summary of what this role is for."
                        error={errors.description}
                    >
                        <Textarea
                            name="description"
                            rows={2}
                            value={data.description}
                            onChange={(e) =>
                                setData("description", e.target.value)
                            }
                        />
                    </Field>
                </div>
            </div>

            <div className="surface-card overflow-hidden">
                <header className="border-b border-border px-4 py-3">
                    <h3 className="text-sm font-semibold text-text">
                        Permissions
                    </h3>
                    <p className="mt-0.5 text-xs text-muted">
                        Tick the capabilities this role grants. Groups can be
                        toggled with “Select all”.
                    </p>
                </header>
                <div className="space-y-4 p-4">
                    {groups.map((group) => {
                        const names = group.permissions.map((p) => p.name);
                        const allSelected =
                            names.length > 0 &&
                            names.every((n) => data.permissions.includes(n));
                        return (
                            <div key={group.group}>
                                <div className="mb-2 flex items-center justify-between gap-3">
                                    <h4 className="text-sm font-semibold text-text-soft">
                                        {group.group}
                                    </h4>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            toggleGroup(names, allSelected)
                                        }
                                        className="text-xs font-medium text-primary hover:underline"
                                    >
                                        {allSelected
                                            ? "Clear all"
                                            : "Select all"}
                                    </button>
                                </div>
                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    {group.permissions.map((p) => (
                                        <label
                                            key={p.name}
                                            className="flex items-start gap-2 text-sm text-text-soft"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={data.permissions.includes(
                                                    p.name,
                                                )}
                                                onChange={() => toggle(p.name)}
                                                className="mt-0.5 h-4 w-4 rounded border-border-strong text-primary focus:ring-primary/40"
                                            />
                                            <span>
                                                <span className="font-medium text-text">
                                                    {p.label}
                                                </span>
                                                <br />
                                                <code className="text-xs text-muted">
                                                    {p.name}
                                                </code>
                                            </span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {isEdit ? "Save changes" : "Create role"}
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
