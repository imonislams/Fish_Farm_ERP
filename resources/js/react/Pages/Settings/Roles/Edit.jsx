import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import RoleForm from "./RoleForm";

/** Edit Role. */
function RolesEdit({ role, groups = [], selected = [] }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="Edit Role" />
            <PageHeader
                title={`Edit — ${role.label}`}
                subtitle="Update the role and its granted permissions."
                breadcrumb={[
                    { label: "Settings" },
                    { label: "Roles", href: routes["settings.roles.index"] },
                    { label: "Edit" },
                ]}
            />
            <RoleForm
                role={{ ...role, selected }}
                groups={groups}
                action={routes["settings.roles.update"]?.replace(
                    "{role}",
                    role.id,
                )}
                method="put"
            />

            {role.is_system && (
                <div className="mt-5 surface-card border-warning/40 p-4 text-sm text-text-soft">
                    This is a <strong className="text-text">system role</strong>
                    . Its machine name is fixed and it cannot be deleted,
                    because the application references it by name. You may still
                    adjust its permissions.
                </div>
            )}
        </>
    );
}

export default withLayout(RolesEdit, "Edit Role");
