import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import InspectionForm from "./InspectionForm";

/** New Inspection. */
function InspectionsCreate({
    options = {},
    parameters = {},
    selectedPondId = null,
    defaultDate = "",
}) {
    const { props } = usePage();
    const routes = props.routes || {};

    const hasPonds = Object.keys(options.pondOptions || {}).length > 0;

    return (
        <>
            <Head title="New Inspection" />
            <PageHeader
                title="New Inspection"
                subtitle="Record a pond inspection — the readings taken and the health conclusion reached."
                breadcrumb={[
                    { label: "FCR & Growth" },
                    {
                        label: "Pond Inspection",
                        href: routes["fcr.inspections.index"],
                    },
                    { label: "New Inspection" },
                ]}
            />

            <div className="mt-5 max-w-3xl">
                {!hasPonds ? (
                    <Card tone="warning" title="No ponds exist yet">
                        <p className="text-sm text-text-soft">
                            An inspection belongs to a pond.
                        </p>
                    </Card>
                ) : (
                    <Card
                        title="Inspection details"
                        subtitle="Fields marked with * are required."
                    >
                        <InspectionForm
                            pondOptions={options.pondOptions || {}}
                            statusOptions={options.statusOptions || {}}
                            parameters={parameters}
                            selectedPondId={selectedPondId}
                            defaultDate={defaultDate}
                            action={routes["fcr.inspections.store"]}
                            method="post"
                        />
                    </Card>
                )}
            </div>
        </>
    );
}

export default withLayout(InspectionsCreate, "New Inspection");
