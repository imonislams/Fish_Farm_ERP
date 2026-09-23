import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import Button from "../../../Components/Button";
import { Field, Input } from "../../../Components/Form";

/** User Profile — the signed-in user's own account details. */
function ProfileEdit({ user, permissions = [] }) {
    const { props } = usePage();
    const routes = props.routes || {};

    const { data, setData, put, processing, errors } = useForm({
        name: user.name ?? "",
        email: user.email ?? "",
        current_password: "",
        password: "",
        password_confirmation: "",
    });

    const submit = (e) => {
        e.preventDefault();
        put(routes["settings.profile.update"], { preserveScroll: true });
    };

    return (
        <>
            <Head title="User Profile" />

            <PageHeader
                title="User Profile"
                subtitle="Manage your own account details."
                breadcrumb={[{ label: "Settings" }, { label: "User Profile" }]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <form onSubmit={submit} className="space-y-4">
                        <Card title="Account details">
                            <div className="space-y-4">
                                <Field
                                    label="Full name"
                                    name="name"
                                    required
                                    error={errors.name}
                                >
                                    <Input
                                        name="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData("name", e.target.value)
                                        }
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
                                        onChange={(e) =>
                                            setData("email", e.target.value)
                                        }
                                    />
                                </Field>
                            </div>
                        </Card>

                        <Card
                            title="Change password"
                            subtitle="Leave these blank to keep your current password."
                        >
                            <div className="space-y-4">
                                <Field
                                    label="Current password"
                                    name="current_password"
                                    hint="Required only when setting a new password."
                                    error={errors.current_password}
                                >
                                    <Input
                                        name="current_password"
                                        type="password"
                                        autoComplete="current-password"
                                        value={data.current_password}
                                        onChange={(e) =>
                                            setData(
                                                "current_password",
                                                e.target.value,
                                            )
                                        }
                                    />
                                </Field>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field
                                        label="New password"
                                        name="password"
                                        hint="Minimum 8 characters."
                                        error={errors.password}
                                    >
                                        <Input
                                            name="password"
                                            type="password"
                                            autoComplete="new-password"
                                            value={data.password}
                                            onChange={(e) =>
                                                setData(
                                                    "password",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Confirm new password"
                                        name="password_confirmation"
                                        error={errors.password_confirmation}
                                    >
                                        <Input
                                            name="password_confirmation"
                                            type="password"
                                            autoComplete="new-password"
                                            value={data.password_confirmation}
                                            onChange={(e) =>
                                                setData(
                                                    "password_confirmation",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>
                            </div>
                        </Card>

                        <div className="flex flex-wrap items-center gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={processing}
                            >
                                Save changes
                            </Button>
                            {routes["dashboard"] && (
                                <Button
                                    href={routes["dashboard"]}
                                    variant="outline"
                                >
                                    Cancel
                                </Button>
                            )}
                        </div>
                    </form>
                </div>

                <div className="space-y-4">
                    <Card title="Your account">
                        <div className="flex items-center gap-3">
                            <span className="gradient-primary grid h-12 w-12 shrink-0 place-items-center rounded-full text-base font-bold text-white">
                                {(user.name || "?").slice(0, 1).toUpperCase()}
                            </span>
                            <div className="min-w-0">
                                <p className="truncate font-medium text-text">
                                    {user.name}
                                </p>
                                <p className="truncate text-xs text-muted">
                                    {user.email}
                                </p>
                            </div>
                        </div>

                        <dl className="mt-4 space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Role</dt>
                                <dd className="font-medium text-text">
                                    {user.role || "—"}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Status</dt>
                                <dd>
                                    {user.is_active ? (
                                        <Badge tone="success" dot>
                                            Active
                                        </Badge>
                                    ) : (
                                        <Badge tone="danger" dot>
                                            Inactive
                                        </Badge>
                                    )}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Company</dt>
                                <dd className="font-medium text-text">
                                    {user.company || "—"}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Member since</dt>
                                <dd className="font-medium text-text">
                                    {user.created_at || "—"}
                                </dd>
                            </div>
                        </dl>

                        <div className="mt-4 surface-card border-info/30 p-3 text-xs text-text-soft">
                            Your <strong className="text-text">role</strong>,
                            account status and company can only be changed by an
                            administrator. You cannot modify your own access
                            level.
                        </div>
                    </Card>

                    <Card
                        title="Your permissions"
                        subtitle={
                            user.role
                                ? `Granted through the “${user.role}” role.`
                                : "You have no role assigned."
                        }
                    >
                        {permissions.length === 0 ? (
                            <p className="text-sm text-muted">
                                No permissions granted.
                            </p>
                        ) : (
                            <div className="flex flex-wrap gap-1">
                                {permissions.map((p) => (
                                    <code
                                        key={p}
                                        className="rounded bg-surface-muted px-1.5 py-0.5 text-xs text-text-soft"
                                    >
                                        {p}
                                    </code>
                                ))}
                            </div>
                        )}
                    </Card>
                </div>
            </div>
        </>
    );
}

export default withLayout(ProfileEdit, "User Profile");
