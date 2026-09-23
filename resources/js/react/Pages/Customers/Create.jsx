import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card } from "../../Components/Card";
import CustomerForm from "./CustomerForm";

/** New Customer. */
function CustomersCreate() {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="New Customer" />
            <PageHeader
                title="New Customer"
                subtitle="Add a buyer of fish."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Customers", href: routes["customers.index"] },
                    { label: "New Customer" },
                ]}
            />
            <div className="mt-5 max-w-3xl">
                <Card
                    title="Customer details"
                    subtitle="Fields marked with * are required."
                >
                    <CustomerForm
                        action={routes["customers.store"]}
                        method="post"
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(CustomersCreate, "New Customer");
