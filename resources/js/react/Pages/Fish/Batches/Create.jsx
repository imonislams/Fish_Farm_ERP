import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import BatchForm from "./BatchForm";

/** Create a fish batch (stocking cycle). */
function BatchesCreate({ options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="New Fish Batch" />
            <PageHeader
                title="New Fish Batch"
                subtitle="Start a stocking cycle for a pond and species."
                breadcrumb={[
                    { label: "Fish Stock" },
                    { label: "Batches", href: routes["fish.batches.index"] },
                    { label: "New" },
                ]}
            />
            <div className="mt-5 max-w-3xl">
                <BatchForm
                    options={options}
                    mode="create"
                    defaultDate={defaultDate}
                />
            </div>
        </>
    );
}

export default withLayout(BatchesCreate, "New Fish Batch");
