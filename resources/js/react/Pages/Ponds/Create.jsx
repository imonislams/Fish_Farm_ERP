import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card } from "../../Components/Card";
import Button from "../../Components/Button";
import PondForm from "./PondForm";

/** New Pond — the migrated create form. */
function PondsCreate({ options = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const hasTypes = Object.keys(options.types || {}).length > 0;

    return (
        <>
            <Head title="New Pond" />

            <PageHeader
                title="New Pond"
                subtitle="Register a pond with its type, measurements and status."
                breadcrumb={[
                    { label: "Pond Management" },
                    { label: "All Ponds", href: routes["ponds.index"] },
                    { label: "New Pond" },
                ]}
            />

            <div className="mt-5 max-w-3xl">
                {!hasTypes ? (
                    <Card tone="warning" title="No active pond type available">
                        <p className="text-sm text-text-soft">
                            A pond must be classified, so create a pond type
                            first.
                        </p>
                        {can("pond_type.create") &&
                            routes["ponds.types.create"] && (
                                <div className="mt-3">
                                    <Button
                                        href={routes["ponds.types.create"]}
                                        variant="primary"
                                        size="sm"
                                    >
                                        New Pond Type
                                    </Button>
                                </div>
                            )}
                    </Card>
                ) : (
                    <Card
                        title="Pond details"
                        subtitle="Fields marked with * are required."
                    >
                        <PondForm
                            action={routes["ponds.store"]}
                            method="post"
                            options={options}
                        />
                    </Card>
                )}
            </div>
        </>
    );
}

export default withLayout(PondsCreate, "New Pond");
