import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import TypeForm from "./TypeForm";

/** New Pond Type. */
function PondTypesCreate() {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="New Pond Type" />
            <PageHeader
                title="New Pond Type"
                subtitle="Add a classification that ponds can be assigned to."
                breadcrumb={[
                    { label: "Pond Management" },
                    { label: "Pond Types", href: routes["ponds.types.index"] },
                    { label: "New" },
                ]}
            />
            <div className="mt-5 max-w-2xl">
                <Card
                    title="Pond type details"
                    subtitle="Fields marked with * are required."
                >
                    <TypeForm
                        action={routes["ponds.types.store"]}
                        method="post"
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(PondTypesCreate, "New Pond Type");
