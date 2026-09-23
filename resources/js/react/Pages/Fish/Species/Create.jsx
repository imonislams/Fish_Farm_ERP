import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import SpeciesForm from "./SpeciesForm";

/** New Fish Species. */
function FishSpeciesCreate() {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="New Fish Species" />
            <PageHeader
                title="New Fish Species"
                subtitle="Add a species to the catalogue used for stockings and harvests."
                breadcrumb={[
                    { label: "Fish Stock" },
                    {
                        label: "Fish Species",
                        href: routes["fish.species.index"],
                    },
                    { label: "New Species" },
                ]}
            />
            <div className="mt-5 max-w-2xl">
                <Card
                    title="Species details"
                    subtitle="Fields marked with * are required."
                >
                    <SpeciesForm
                        action={routes["fish.species.store"]}
                        method="post"
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(FishSpeciesCreate, "New Fish Species");
