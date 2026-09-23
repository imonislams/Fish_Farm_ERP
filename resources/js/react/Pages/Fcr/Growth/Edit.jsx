import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import GrowthForm from "./GrowthForm";

/** Edit Growth Sample. */
function GrowthEdit({ record, options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="Edit Growth Sample" />
            <PageHeader
                title="Edit Growth Sample"
                subtitle="Correct a recorded sample. FCR for the pond is recalculated from the corrected figure."
                breadcrumb={[
                    { label: "FCR & Growth" },
                    { label: "Growth Monitoring", href: routes["fcr.growth"] },
                    { label: "Edit Sample" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                <Card
                    title="Sample details"
                    subtitle="Fields marked with * are required."
                >
                    <GrowthForm
                        record={record}
                        pondOptions={options.pondOptions || {}}
                        defaultDate={defaultDate}
                        action={routes["fcr.growth.update"]?.replace(
                            "{growth}",
                            record.id,
                        )}
                        method="put"
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(GrowthEdit, "Edit Growth Sample");
