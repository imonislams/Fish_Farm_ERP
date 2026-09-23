import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable } from "../../../Components/DataTable";
import Button from "../../../Components/Button";

/** Roles list — roles bundle permissions; one role per user. */
function RolesIndex({ roles = [] }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const columns = [
        {
            key: "label",
            label: "Role",
            render: (r) => (
                <div>
                    <p className="font-medium text-text">{r.label}</p>
                    {r.description && (
                        <p className="mt-0.5 text-xs text-muted">
                            {r.description}
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: "name",
            label: "Machine name",
            render: (r) => (
                <code className="rounded bg-surface-muted px-1.5 py-0.5 text-xs text-text-soft">
                    {r.name}
                </code>
            ),
        },
        {
            key: "permissions_count",
            label: "Permissions",
            align: "center",
            render: (r) => <Badge tone="primary">{r.permissions_count}</Badge>,
        },
        {
            key: "users_count",
            label: "Users",
            align: "center",
            render: (r) => <Badge tone="default">{r.users_count}</Badge>,
        },
        {
            key: "is_system",
            label: "Type",
            align: "center",
            render: (r) =>
                r.is_system ? (
                    <Badge tone="warning">System</Badge>
                ) : (
                    <Badge tone="default">Custom</Badge>
                ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {can("roles.update") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="outline" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("roles.delete") && !r.is_system && r.urls?.destroy && (
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={async () => {
                                if (
                                    !(await confirm({ title: "Confirm", description: `Delete the role "${r.label}"? This cannot be undone.` }))
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
            <Head title="Roles" />

            <PageHeader
                title="Roles"
                subtitle="Roles bundle permissions. Assign one role to each user."
                breadcrumb={[{ label: "Settings" }, { label: "Roles" }]}
                actions={
                    can("roles.create") &&
                    routes["settings.roles.create"] && (
                        <Button
                            href={routes["settings.roles.create"]}
                            variant="secondary"
                            icon="users"
                        >
                            Create role
                        </Button>
                    )
                }
            />

            <div className="mt-5">
                <Card
                    padded={false}
                    title="All roles"
                    actions={<Badge tone="info">{roles.length} roles</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={roles}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No roles found
                                </h4>
                            </div>
                        }
                    />
                </Card>
            </div>

            <div className="mt-5 surface-card border-info/30 p-4 text-sm text-text-soft">
                <strong className="text-text">System roles</strong> are seeded
                and protected — their machine name cannot be changed and they
                cannot be deleted, because the application references them by
                name. Create a <em>custom</em> role when you need a different
                permission mix.
            </div>
        </>
    );
}

export default withLayout(RolesIndex, "Roles");
