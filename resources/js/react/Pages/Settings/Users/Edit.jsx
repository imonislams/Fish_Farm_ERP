import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import UserForm from "./UserForm";

/** Edit User. */
function UsersEdit({ user, roles = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="Edit User" />
            <PageHeader
                title={`Edit — ${user.name}`}
                subtitle="Update this user's details, role and status."
                breadcrumb={[
                    { label: "Settings" },
                    { label: "Users", href: routes["settings.users.index"] },
                    { label: "Edit" },
                ]}
            />
            <div className="mt-5 max-w-2xl">
                <Card
                    title="User details"
                    subtitle="Fields marked with * are required."
                >
                    <UserForm
                        user={user}
                        roles={roles}
                        isSelf={user.is_self}
                        action={routes["settings.users.update"]?.replace(
                            "{user}",
                            user.id,
                        )}
                        method="put"
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(UsersEdit, "Edit User");
