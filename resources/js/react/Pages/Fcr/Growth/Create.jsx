import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import GrowthForm from "./GrowthForm";

/** Record Growth Sample. */
function GrowthCreate({
    options = {},
    selectedPondId = null,
    defaultDate = "",
}) {
    const { props } = usePage();
    const routes = props.routes || {};

    const hasPonds = Object.keys(options.pondOptions || {}).length > 0;

    return (
        <>
            <Head title="Record Growth Sample" />
            <PageHeader
                title="Record Growth Sample"
                subtitle="Weigh a sample of fish to track growth. FCR uses the latest sample as the current weight."
                breadcrumb={[
                    { label: "FCR & Growth" },
                    { label: "Growth Monitoring", href: routes["fcr.growth"] },
                    { label: "Record Sample" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                {!hasPonds ? (
                    <Card tone="warning" title="No ponds exist yet">
                        <p className="text-sm text-text-soft">
                            A growth sample belongs to a pond.
                        </p>
                    </Card>
                ) : (
                    <Card
                        title="Sample details"
                        subtitle="Fields marked with * are required."
                    >
                        <GrowthForm
                            pondOptions={options.pondOptions || {}}
                            selectedPondId={selectedPondId}
                            defaultDate={defaultDate}
                            action={routes["fcr.growth.store"]}
                            method="post"
                        />
                    </Card>
                )}
            </div>
        </>
    );
}

export default withLayout(GrowthCreate, "Record Growth Sample");
