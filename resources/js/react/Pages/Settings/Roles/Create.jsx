import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import RoleForm from "./RoleForm";

/** Create Role. */
function RolesCreate({ groups = [], selected = [] }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="Create Role" />
            <PageHeader
                title="Create Role"
                subtitle="Define a role and choose exactly which permissions it grants."
                breadcrumb={[
                    { label: "Settings" },
                    { label: "Roles", href: routes["settings.roles.index"] },
                    { label: "Create" },
                ]}
            />
            <RoleForm
                groups={groups}
                action={routes["settings.roles.store"]}
                method="post"
            />
        </>
    );
}

export default withLayout(RolesCreate, "Create Role");
