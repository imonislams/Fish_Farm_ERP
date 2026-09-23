import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card } from "../../Components/Card";
import Button from "../../Components/Button";
import SaleForm from "./SaleForm";

/** New Sale. */
function SalesCreate({ options = {}, defaultDate = "", nextInvoiceNo = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const hasCustomers = Object.keys(options.customerOptions || {}).length > 0;

    return (
        <>
            <Head title="New Sale" />
            <PageHeader
                title="New Sale"
                subtitle="Sell fish to a customer. The totals are calculated from the line items."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Fish Sales", href: routes["sales.list"] },
                    { label: "New Sale" },
                ]}
            />

            <div className="mt-5">
                {!hasCustomers ? (
                    <Card tone="warning" title="No customers exist yet">
                        <p className="text-sm text-text-soft">
                            A sale needs a customer, so add one first.
                        </p>
                        {can("customer.create") &&
                            routes["customers.create"] && (
                                <div className="mt-3">
                                    <Button
                                        href={routes["customers.create"]}
                                        variant="primary"
                                        size="sm"
                                    >
                                        New Customer
                                    </Button>
                                </div>
                            )}
                    </Card>
                ) : (
                    <Card
                        title="Sale details"
                        subtitle="Fields marked with * are required."
                    >
                        <SaleForm
                            options={options}
                            defaultDate={defaultDate}
                            nextInvoiceNo={nextInvoiceNo}
                            action={routes["sales.store"]}
                            method="post"
                        />
                    </Card>
                )}
            </div>
        </>
    );
}

export default withLayout(SalesCreate, "New Sale");
