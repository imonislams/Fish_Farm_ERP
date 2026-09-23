import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, Input, Select } from "../../../Components/Form";

/** Users list — manage who can access the company's ERP. */
function UsersIndex({ users, roles = {}, filters = {}, currentUserId = null }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [search, setSearch] = React.useState(filters.search || "");
    const [status, setStatus] = React.useState(filters.status || "");
    const [role, setRole] = React.useState(filters.role ?? "");
    const [busy, setBusy] = React.useState(false);

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["settings.users.index"] || window.location.pathname,
            { search, status, role },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["settings.users.index"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = search !== "" || status !== "" || role !== "";

    const columns = [
        {
            key: "name",
            label: "Name",
            render: (r) => (
                <div className="flex items-center gap-3">
                    <span className="gradient-primary grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-bold text-white">
                        {(r.name || "?").slice(0, 1).toUpperCase()}
                    </span>
                    <span className="font-medium text-text">{r.name}</span>
                    {r.is_self && <Badge tone="info">You</Badge>}
                </div>
            ),
        },
        {
            key: "email",
            label: "Email",
            render: (r) => <span className="text-muted">{r.email}</span>,
        },
        { key: "role", label: "Role", render: (r) => r.role || "—" },
        {
            key: "is_active",
            label: "Status",
            align: "center",
            render: (r) =>
                r.is_active ? (
                    <Badge tone="success" dot>
                        Active
                    </Badge>
                ) : (
                    <Badge tone="danger" dot>
                        Inactive
                    </Badge>
                ),
        },
        {
            key: "created_at",
            label: "Created",
            render: (r) => (
                <span className="whitespace-nowrap text-muted">
                    {r.created_at || "—"}
                </span>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {can("users.update") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="outline" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("users.update") && !r.is_self && r.urls?.toggle && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={async () => {
                                const msg = r.is_active
                                    ? `Deactivate ${r.name}? They will be signed out and unable to log in.`
                                    : `Activate ${r.name}?`;
                                if (
                                    !(await confirm({
                                        title: "Confirm",
                                        description: msg,
                                    }))
                                )
                                    return;
                                router.patch(
                                    r.urls.toggle,
                                    {},
                                    { preserveScroll: true },
                                );
                            }}
                        >
                            {r.is_active ? "Deactivate" : "Activate"}
                        </Button>
                    )}
                    {can("users.delete") && !r.is_self && r.urls?.destroy && (
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={async () => {
                                if (
                                    !(await confirm({
                                        title: "Confirm",
                                        description: `Delete ${r.name}? This cannot be undone.`,
                                    }))
                                )
                                    return;
                                router.delete(r.urls.destroy, {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            Delete
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Users" />

            <PageHeader
                title="Users"
                subtitle="Manage who can access this company's Fish Farm ERP."
                breadcrumb={[{ label: "Settings" }, { label: "Users" }]}
                actions={
                    can("users.create") &&
                    routes["settings.users.create"] && (
                        <Button
                            href={routes["settings.users.create"]}
                            variant="secondary"
                            icon="users"
                        >
                            Create user
                        </Button>
                    )
                }
            />

            <div className="mt-5">
                <Card title="Search &amp; filters">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <Field label="Search" name="search">
                            <Input
                                name="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Name or email…"
                            />
                        </Field>

                        <Field label="Status" name="status">
                            <Select
                                name="status"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                placeholder="All statuses"
                                options={{
                                    active: "Active",
                                    inactive: "Inactive",
                                }}
                            />
                        </Field>

                        <Field label="Role" name="role">
                            <Select
                                name="role"
                                value={role}
                                onChange={(e) => setRole(e.target.value)}
                                placeholder="All roles"
                                options={roles}
                            />
                        </Field>

                        <div className="flex items-end gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={busy}
                            >
                                Apply
                            </Button>
                            {hasFilters && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={clear}
                                    disabled={busy}
                                >
                                    Clear
                                </Button>
                            )}
                        </div>
                    </form>
                </Card>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="All users"
                    actions={<Badge tone="info">{users.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={users.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No users found
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or create the first
                                    user for this company.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={users} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(UsersIndex, "Users");
