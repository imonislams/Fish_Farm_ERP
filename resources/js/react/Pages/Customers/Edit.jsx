import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money, usePermission } from "../../Components/Page";
import { useConfirm } from "../../Components/ConfirmModal";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import Button from "../../Components/Button";
import CustomerForm from "./CustomerForm";

/** Edit Customer. */
function CustomersEdit({ customer, due = 0 }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const destroy = async () => {
        if (
            !(await confirm({
                title: "Confirm",
                description: `Delete customer "${customer.name}"? This cannot be undone.`,
            }))
        )
            return;
        router.delete(
            routes["customers.destroy"]?.replace("{customer}", customer.id),
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Edit — ${customer.name}`} />
            <PageHeader
                title={`Edit — ${customer.name}`}
                subtitle="Update this customer's details and status."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Customers", href: routes["customers.index"] },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <Card
                        title="Customer details"
                        subtitle="Fields marked with * are required."
                    >
                        <CustomerForm
                            customer={customer}
                            action={routes["customers.update"]?.replace(
                                "{customer}",
                                customer.id,
                            )}
                            method="put"
                        />
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card title="Account">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Current due</dt>
                                <dd>
                                    <Badge
                                        tone={
                                            due > 0
                                                ? "warning"
                                                : due < 0
                                                  ? "info"
                                                  : "default"
                                        }
                                    >
                                        {money(due)}
                                    </Badge>
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Sales</dt>
                                <dd className="font-medium text-text">
                                    {customer.sales_count}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Payments</dt>
                                <dd className="font-medium text-text">
                                    {customer.payments_count}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Status</dt>
                                <dd>
                                    {customer.is_active ? (
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

                    {!customer.deletable ? (
                        <div className="surface-card border border-warning/40 p-4 text-sm text-text-soft">
                            This customer has sales or payments and cannot be
                            deleted. Mark them inactive instead.
                        </div>
                    ) : (
                        can("customer.delete") && (
                            <Card title="Danger zone" tone="danger">
                                <p className="text-sm text-muted">
                                    This customer has no history, so they can be
                                    removed.
                                </p>
                                <div className="mt-3">
                                    <Button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        onClick={destroy}
                                    >
                                        Delete customer
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

export default withLayout(CustomersEdit, "Edit Customer");
