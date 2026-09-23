import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import Button from "../../../Components/Button";
import CategoryForm from "./CategoryForm";

/** Edit Expense Category. */
function CategoriesEdit({ category }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const destroy = async () => {
        if (
            !(await confirm({
                title: "Confirm",
                description: `Delete category "${category.name}"? This cannot be undone.`,
            }))
        )
            return;
        router.delete(
            routes["finance.categories.destroy"]?.replace(
                "{category}",
                category.id,
            ),
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Edit — ${category.name}`} />
            <PageHeader
                title={`Edit — ${category.name}`}
                subtitle="Update this expense category."
                breadcrumb={[
                    { label: "Finance" },
                    {
                        label: "Expense Categories",
                        href: routes["finance.categories.index"],
                    },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <Card
                        title="Category details"
                        subtitle="Fields marked with * are required."
                    >
                        <CategoryForm
                            category={category}
                            action={routes[
                                "finance.categories.update"
                            ]?.replace("{category}", category.id)}
                            method="put"
                        />
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card title="Usage">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Expenses</dt>
                                <dd className="font-medium text-text">
                                    {category.expenses_count}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Status</dt>
                                <dd>
                                    {category.is_active ? (
                                        <Badge tone="success" dot>
                                            Active
                                        </Badge>
                                    ) : (
                                        <Badge tone="danger" dot>
                                            Inactive
                                        </Badge>
                                    )}
                                </dd>
                            </div>
                        </dl>
                    </Card>

                    {!category.deletable ? (
                        <div className="surface-card border border-warning/40 p-4 text-sm text-text-soft">
                            This category is used by expenses and cannot be
                            deleted. Mark it inactive instead.
                        </div>
                    ) : (
                        can("expense.category.manage") && (
                            <Card title="Danger zone" tone="danger">
                                <p className="text-sm text-muted">
                                    This category is not used, so it can be
                                    removed.
                                </p>
                                <div className="mt-3">
                                    <Button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        onClick={destroy}
                                    >
                                        Delete category
                                    </Button>
                                </div>
                            </Card>
                        )
                    )}
                </div>
            </div>
        </>
    );
}

export default withLayout(CategoriesEdit, "Edit Expense Category");
