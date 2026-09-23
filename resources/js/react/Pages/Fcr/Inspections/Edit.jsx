import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import InspectionForm from "./InspectionForm";

/** Edit Inspection. */
function InspectionsEdit({
    inspection,
    options = {},
    parameters = {},
    defaultDate = "",
}) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="Edit Inspection" />
            <PageHeader
                title="Edit Inspection"
                subtitle="Correct a recorded inspection and its readings."
                breadcrumb={[
                    { label: "FCR & Growth" },
                    {
                        label: "Pond Inspection",
                        href: routes["fcr.inspections.index"],
                    },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5 max-w-3xl">
                <Card
                    title="Inspection details"
                    subtitle="Fields marked with * are required."
                >
                    <InspectionForm
                        inspection={inspection}
                        pondOptions={options.pondOptions || {}}
                        statusOptions={options.statusOptions || {}}
                        parameters={parameters}
                        defaultDate={defaultDate}
                        action={routes["fcr.inspections.update"]?.replace(
                            "{inspection}",
                            inspection.id,
                        )}
                        method="put"
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(InspectionsEdit, "Edit Inspection");
