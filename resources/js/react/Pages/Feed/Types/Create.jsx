import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import FeedTypeForm from "./TypeForm";

/** New Food Type. */
function FeedTypesCreate() {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="New Food Type" />
            <PageHeader
                title="New Food Type"
                subtitle="Add a feed product to the catalogue."
                breadcrumb={[
                    { label: "Food Management" },
                    { label: "Food Types", href: routes["feed.types.index"] },
                    { label: "New Food Type" },
                ]}
            />
            <div className="mt-5 max-w-3xl">
                <Card
                    title="Feed type details"
                    subtitle="Fields marked with * are required."
                >
                    <FeedTypeForm
                        action={routes["feed.types.store"]}
                        method="post"
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(FeedTypesCreate, "New Food Type");
