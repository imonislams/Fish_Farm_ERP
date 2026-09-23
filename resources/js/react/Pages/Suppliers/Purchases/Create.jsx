import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import Button from "../../../Components/Button";
import PurchaseForm from "./PurchaseForm";

/** New Purchase. */
function PurchasesCreate({ options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const hasSuppliers = Object.keys(options.supplierOptions || {}).length > 0;

    return (
        <>
            <Head title="New Purchase" />
            <PageHeader
                title="New Purchase"
                subtitle="Record goods bought from a supplier. The totals are calculated from the line items."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    {
                        label: "Supplier Purchases",
                        href: routes["suppliers.purchases.index"],
                    },
                    { label: "New Purchase" },
                ]}
            />

            <div className="mt-5">
                {!hasSuppliers ? (
                    <Card tone="warning" title="No suppliers exist yet">
                        <p className="text-sm text-text-soft">
                            A purchase needs a supplier, so add one first.
                        </p>
                        {can("supplier.create") &&
                            routes["suppliers.create"] && (
                                <div className="mt-3">
                                    <Button
                                        href={routes["suppliers.create"]}
                                        variant="primary"
                                        size="sm"
                                    >
                                        New Supplier
                                    </Button>
                                </div>
                            )}
                    </Card>
                ) : (
                    <Card
                        title="Purchase details"
                        subtitle="Fields marked with * are required."
                    >
                        <PurchaseForm
                            options={options}
                            defaultDate={defaultDate}
                            action={routes["suppliers.purchases.store"]}
                            method="post"
                        />
                    </Card>
                )}
            </div>
        </>
    );
}

export default withLayout(PurchasesCreate, "New Purchase");
