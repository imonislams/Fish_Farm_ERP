import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import UserForm from "./UserForm";

/** Create User. */
function UsersCreate({ roles = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="Create User" />
            <PageHeader
                title="Create User"
                subtitle="Add a new user who can access this company's ERP."
                breadcrumb={[
                    { label: "Settings" },
                    { label: "Users", href: routes["settings.users.index"] },
                    { label: "Create User" },
                ]}
            />
            <div className="mt-5 max-w-2xl">
                <Card
                    title="User details"
                    subtitle="Fields marked with * are required."
                >
                    <UserForm
                        roles={roles}
                        action={routes["settings.users.store"]}
                        method="post"
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(UsersCreate, "Create User");
