import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card } from "../../Components/Card";
import SupplierForm from "./SupplierForm";

/** New Supplier. */
function SuppliersCreate() {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="New Supplier" />
            <PageHeader
                title="New Supplier"
                subtitle="Add a vendor the farm buys from."
                breadcrumb={[
                    { label: "Suppliers" },
                    { label: "Supplier List", href: routes["suppliers.index"] },
                    { label: "New Supplier" },
                ]}
            />
            <div className="mt-5 max-w-3xl">
                <Card
                    title="Supplier details"
                    subtitle="Fields marked with * are required."
                >
                    <SupplierForm
                        action={routes["suppliers.store"]}
                        method="post"
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(SuppliersCreate, "New Supplier");
