import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card } from "../../Components/Card";
import SaleForm from "./SaleForm";

/** Edit Sale. */
function SalesEdit({ sale, options = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title={`Edit — ${sale.invoice_no}`} />
            <PageHeader
                title={`Edit — ${sale.invoice_no}`}
                subtitle="Update the sale header, its line items and totals."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Fish Sales", href: routes["sales.list"] },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5">
                <Card
                    title="Sale details"
                    subtitle="Fields marked with * are required."
                >
                    <SaleForm
                        sale={sale}
                        options={options}
                        action={routes["sales.update"]?.replace(
                            "{sale}",
                            sale.id,
                        )}
                        method="put"
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(SalesEdit, "Edit Sale");
