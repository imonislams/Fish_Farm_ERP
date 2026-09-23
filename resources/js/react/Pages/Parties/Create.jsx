import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card } from "../../Components/Card";
import PartyForm from "./PartyForm";

/** New Party. */
function PartiesCreate({ typeOptions = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="New Party" />
            <PageHeader
                title="New Party"
                subtitle="Add a generic ledger counterparty."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Parties", href: routes["parties.index"] },
                    { label: "New Party" },
                ]}
            />
            <div className="mt-5 max-w-3xl">
                <Card
                    title="Party details"
                    subtitle="Fields marked with * are required."
                >
                    <PartyForm
                        action={routes["parties.store"]}
                        method="post"
                        typeOptions={typeOptions}
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(PartiesCreate, "New Party");
