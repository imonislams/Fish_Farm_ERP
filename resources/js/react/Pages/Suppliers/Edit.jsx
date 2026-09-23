import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money, usePermission } from "../../Components/Page";
import { useConfirm } from "../../Components/ConfirmModal";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import Button from "../../Components/Button";
import SupplierForm from "./SupplierForm";

/** Edit Supplier. */
function SuppliersEdit({ supplier, due = 0 }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const destroy = async () => {
        if (
            !(await confirm({
                title: "Confirm",
                description: `Delete supplier "${supplier.name}"? This cannot be undone.`,
            }))
        )
            return;
        router.delete(
            routes["suppliers.destroy"]?.replace("{supplier}", supplier.id),
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Edit — ${supplier.name}`} />
            <PageHeader
                title={`Edit — ${supplier.name}`}
                subtitle="Update this supplier's details and status."
                breadcrumb={[
                    { label: "Suppliers" },
                    {
                        label: "Supplier List",
                        href: routes["suppliers.index"],
                    },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <Card
                        title="Supplier details"
                        subtitle="Fields marked with * are required."
                    >
                        <SupplierForm
                            supplier={supplier}
                            action={routes["suppliers.update"]?.replace(
                                "{supplier}",
                                supplier.id,
                            )}
                            method="put"
                        />
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card title="Account">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Current payable</dt>
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
                                <dt className="text-muted">Purchases</dt>
                                <dd className="font-medium text-text">
                                    {supplier.purchases_count}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Payments</dt>
                                <dd className="font-medium text-text">
                                    {supplier.payments_count}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Status</dt>
                                <dd>
                                    {supplier.is_active ? (
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

                    {!supplier.deletable ? (
                        <div className="surface-card border border-warning/40 p-4 text-sm text-text-soft">
                            This supplier has purchases or payments and cannot
                            be deleted. Mark them inactive instead.
                        </div>
                    ) : (
                        can("supplier.delete") && (
                            <Card title="Danger zone" tone="danger">
                                <p className="text-sm text-muted">
                                    This supplier has no history, so they can be
                                    removed.
                                </p>
                                <div className="mt-3">
                                    <Button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        onClick={destroy}
                                    >
                                        Delete supplier
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

export default withLayout(SuppliersEdit, "Edit Supplier");
