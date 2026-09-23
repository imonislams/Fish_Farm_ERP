import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";

/** Permissions reference — every capability, grouped by module (read-only). */
function PermissionsIndex({ groups = [], total = 0 }) {
    const { props } = usePage();
    const routes = props.routes || {};

    const slug = (s) =>
        String(s)
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, "-")
            .replace(/(^-|-$)/g, "");

    return (
        <>
            <Head title="Permissions" />

            <PageHeader
                title="Permissions"
                subtitle="Every capability in the system, grouped by module."
                breadcrumb={[{ label: "Settings" }, { label: "Permissions" }]}
                actions={<Badge tone="info">{total} permissions</Badge>}
            />

            <div className="mt-5 surface-card border-info/30 p-4 text-sm text-text-soft">
                Permissions are defined in <code>config/permissions.php</code>{" "}
                and seeded — they are not created here, because a permission
                only means something once the application enforces it. To change
                what a role can do, edit that role on the{" "}
                {routes["settings.roles.index"] && (
                    <a
                        href={routes["settings.roles.index"]}
                        className="font-medium underline"
                    >
                        Roles
                    </a>
                )}{" "}
                page.
            </div>

            <div className="mt-5 flex-wrap gap-2">
                {groups.map((g) => (
                    <a
                        key={g.group}
                        href={`#group-${slug(g.group)}`}
                        className="rounded-full border-border bg-surface px-3 py-1 text-xs font-medium text-text-soft hover:bg-surface-muted"
                    >
                        {g.group}
                        <span className="ml-1 text-muted">
                            {g.permissions.length}
                        </span>
                    </a>
                ))}
            </div>

            <div className="mt-5 space-y-4">
                {groups.map((g) => (
                    <Card
                        key={g.group}
                        padded={false}
                        title={g.group}
                        actions={
                            <Badge tone="default">{g.permissions.length}</Badge>
                        }
                    >
                        <div
                            id={`group-${slug(g.group)}`}
                            className="table-shell"
                        >
                            <table className="w-full text-sm">
                                <thead className="bg-surface-muted text-xs uppercase text-muted">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium">
                                            Permission
                                        </th>
                                        <th className="px-4 py-3 text-left font-medium">
                                            Label
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Granted to
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {g.permissions.map((p) => (
                                        <tr key={p.name}>
                                            <td className="px-4 py-3">
                                                <code className="rounded bg-surface-muted px-1.5 py-0.5 text-xs text-text-soft">
                                                    {p.name}
                                                </code>
                                            </td>
                                            <td className="px-4 py-3 text-text-soft">
                                                {p.label}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap justify-end gap-1">
                                                    {p.roles &&
                                                    p.roles.length > 0 ? (
                                                        p.roles.map((role) => (
                                                            <Badge
                                                                key={role}
                                                                tone="primary"
                                                            >
                                                                {role}
                                                            </Badge>
                                                        ))
                                                    ) : (
                                                        <span className="text-xs text-muted">
                                                            Not granted to any
                                                            role
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                ))}
            </div>
        </>
    );
}

export default withLayout(PermissionsIndex, "Permissions");
