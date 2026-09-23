import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import Button from "../../../Components/Button";
import { Field, Input, Select, Textarea } from "../../../Components/Form";

/**
 * Company Settings — the single business identity (Version 1 is single-company).
 *
 * The logo is uploaded through Inertia's `useForm` with `forceFormData`, so the
 * file rides along with the normal JSON fields — no separate full-page submit.
 */
function CompanyEdit({ company, timezones = {}, currencies = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};

    const { data, setData, post, processing, errors } = useForm({
        _method: "put",
        name: company.name ?? "",
        code: company.code ?? "",
        phone: company.phone ?? "",
        email: company.email ?? "",
        address: company.address ?? "",
        currency: company.currency ?? "BDT",
        timezone: company.timezone ?? "Asia/Dhaka",
        status: company.status ?? "active",
        logo: null,
        remove_logo: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["settings.company.update"], {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Company Settings" />

            <PageHeader
                title="Company Settings"
                subtitle="The single business identity used throughout the system."
                breadcrumb={[{ label: "Settings" }, { label: "Company" }]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <form onSubmit={submit} className="space-y-4">
                        <Card
                            title="Business identity"
                            subtitle="Name, contact details and locale."
                        >
                            <div className="space-y-4">
                                <Field
                                    label="Company / Farm Name"
                                    name="name"
                                    required
                                    hint="Shown in the sidebar, header and login screen."
                                    error={errors.name}
                                >
                                    <Input
                                        name="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData("name", e.target.value)
                                        }
                                        autoFocus
                                    />
                                </Field>

                                <Field
                                    label="Short Code"
                                    name="code"
                                    hint="Optional unique code, e.g. FISHFARM."
                                    error={errors.code}
                                >
                                    <Input
                                        name="code"
                                        value={data.code}
                                        onChange={(e) =>
                                            setData("code", e.target.value)
                                        }
                                    />
                                </Field>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field
                                        label="Phone"
                                        name="phone"
                                        error={errors.phone}
                                    >
                                        <Input
                                            name="phone"
                                            value={data.phone}
                                            onChange={(e) =>
                                                setData("phone", e.target.value)
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Email"
                                        name="email"
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

                                <Field
                                    label="Address"
                                    name="address"
                                    error={errors.address}
                                >
                                    <Textarea
                                        name="address"
                                        rows={3}
                                        value={data.address}
                                        onChange={(e) =>
                                            setData("address", e.target.value)
                                        }
                                    />
                                </Field>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field
                                        label="Currency"
                                        name="currency"
                                        required
                                        error={errors.currency}
                                    >
                                        <Select
                                            name="currency"
                                            value={data.currency}
                                            onChange={(e) =>
                                                setData(
                                                    "currency",
                                                    e.target.value,
                                                )
                                            }
                                            options={currencies}
                                        />
                                    </Field>

                                    <Field
                                        label="Timezone"
                                        name="timezone"
                                        required
                                        error={errors.timezone}
                                    >
                                        <Select
                                            name="timezone"
                                            value={data.timezone}
                                            onChange={(e) =>
                                                setData(
                                                    "timezone",
                                                    e.target.value,
                                                )
                                            }
                                            options={timezones}
                                        />
                                    </Field>
                                </div>

                                <Field
                                    label="Status"
                                    name="status"
                                    required
                                    hint="Set to inactive to mark the company disabled. It is never deleted."
                                    error={errors.status}
                                >
                                    <Select
                                        name="status"
                                        value={data.status}
                                        onChange={(e) =>
                                            setData("status", e.target.value)
                                        }
                                        options={{
                                            active: "Active",
                                            inactive: "Inactive",
                                        }}
                                    />
                                </Field>
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
                    <Card title="Logo">
                        <div className="flex items-center gap-3">
                            {company.logo_url ? (
                                <img
                                    src={company.logo_url}
                                    alt={company.name}
                                    className="h-14 w-14 rounded-control border-border object-cover"
                                />
                            ) : (
                                <span className="gradient-primary grid h-14 w-14 shrink-0 place-items-center rounded-control text-lg font-bold text-white">
                                    {(company.name || "FF")
                                        .slice(0, 2)
                                        .toUpperCase()}
                                </span>
                            )}

                            <div className="min-w-0 text-sm">
                                <p className="font-medium text-text">
                                    {company.name}
                                </p>
                                <p className="text-xs text-muted">
                                    {company.logo_url
                                        ? "Custom logo uploaded"
                                        : "No logo — initials are shown"}
                                </p>
                            </div>
                        </div>

                        <div className="mt-3">
                            <Field
                                label="Upload logo"
                                name="logo"
                                hint="PNG, JPG, WEBP or SVG. Max 2 MB."
                                error={errors.logo}
                            >
                                <input
                                    type="file"
                                    name="logo"
                                    accept="image/*"
                                    onChange={(e) =>
                                        setData("logo", e.target.files[0])
                                    }
                                    className="w-full rounded-control border-border-strong bg-surface px-3 py-2 text-sm text-text"
                                />
                            </Field>

                            {company.logo_url && (
                                <label className="mt-2 flex items-center gap-2 text-sm text-danger">
                                    <input
                                        type="checkbox"
                                        checked={data.remove_logo}
                                        onChange={(e) =>
                                            setData(
                                                "remove_logo",
                                                e.target.checked,
                                            )
                                        }
                                        className="h-4 w-4 rounded border-border-strong text-danger focus:ring-danger/40"
                                    />
                                    <span>Remove the current logo</span>
                                </label>
                            )}

                            <div className="mt-3">
                                <Button
                                    type="submit"
                                    variant="outline"
                                    size="sm"
                                    className="w-full"
                                    loading={processing}
                                >
                                    Update logo
                                </Button>
                            </div>
                        </div>
                    </Card>

                    <Card title="About this record">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Record ID</dt>
                                <dd className="font-medium text-text">
                                    #{company.id}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Created</dt>
                                <dd className="font-medium text-text">
                                    {company.created_at || "—"}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Last updated</dt>
                                <dd className="font-medium text-text">
                                    {company.updated_at || "—"}
                                </dd>
                            </div>
                        </dl>

                        <div className="mt-4 surface-card border-info/30 p-3 text-xs text-text-soft">
                            Version 1 runs as a{" "}
                            <strong className="text-text">
                                single company
                            </strong>
                            . Multiple users work inside this one farm — there
                            is no multi-tenant switching.
                        </div>
                    </Card>
                </div>
            </div>
        </>
    );
}

export default withLayout(CompanyEdit, "Company Settings");
