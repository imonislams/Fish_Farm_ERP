import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import BatchForm from "./BatchForm";

/** Edit a fish batch's header (status / end date / note). */
function BatchesEdit({ batch = {}, options = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title={`Edit — ${batch.code}`} />
            <PageHeader
                title={`Edit — ${batch.code}`}
                subtitle="Close or reopen the cycle. The quantity always comes from the movement records."
                breadcrumb={[
                    { label: "Fish Stock" },
                    { label: "Batches", href: routes["fish.batches.index"] },
                    { label: batch.code },
                    { label: "Edit" },
                ]}
            />
            <div className="mt-5 max-w-3xl">
                <BatchForm batch={batch} options={options} mode="edit" />
            </div>
        </>
    );
}

export default withLayout(BatchesEdit, "Edit Fish Batch");
