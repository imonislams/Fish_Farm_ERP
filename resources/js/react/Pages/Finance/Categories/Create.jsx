import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import CategoryForm from "./CategoryForm";

/** New Expense Category. */
function CategoriesCreate() {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="New Expense Category" />
            <PageHeader
                title="New Expense Category"
                subtitle="Add a classification for recorded expenses."
                breadcrumb={[
                    { label: "Finance" },
                    {
                        label: "Expense Categories",
                        href: routes["finance.categories.index"],
                    },
                    { label: "New Category" },
                ]}
            />
            <div className="mt-5 max-w-2xl">
                <Card
                    title="Category details"
                    subtitle="Fields marked with * are required."
                >
                    <CategoryForm
                        action={routes["finance.categories.store"]}
                        method="post"
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(CategoriesCreate, "New Expense Category");
