import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import PurchaseForm from "./PurchaseForm";

/** Edit Purchase. */
function PurchasesEdit({ purchase, options = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="Edit Purchase" />
            <PageHeader
                title={`Edit Purchase${purchase.invoice_no ? " — " + purchase.invoice_no : ""}`}
                subtitle="Update the purchase header, its line items and totals."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    {
                        label: "Supplier Purchases",
                        href: routes["suppliers.purchases.index"],
                    },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5">
                <Card
                    title="Purchase details"
                    subtitle="Fields marked with * are required."
                >
                    <PurchaseForm
                        purchase={purchase}
                        options={options}
                        action={routes["suppliers.purchases.update"]?.replace(
                            "{purchase}",
                            purchase.id,
                        )}
                        method="put"
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(PurchasesEdit, "Edit Purchase");
