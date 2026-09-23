import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, num, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import Button from "../../../Components/Button";
import FeedTypeForm from "./TypeForm";

/** Edit Food Type. */
function FeedTypesEdit({ type, currentStockKg = 0 }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const records =
        (type.purchases_count || 0) +
        (type.usages_count || 0) +
        (type.adjustments_count || 0);

    const destroy = async () => {
        if (
            !(await confirm({
                title: "Confirm",
                description: `Delete feed type "${type.name}"? This cannot be undone.`,
            }))
        )
            return;
        router.delete(
            routes["feed.types.destroy"]?.replace("{feedType}", type.id),
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Edit — ${type.name}`} />
            <PageHeader
                title={`Edit — ${type.name}`}
                subtitle="Update this feed type's details and availability."
                breadcrumb={[
                    { label: "Food Management" },
                    { label: "Food Types", href: routes["feed.types.index"] },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <Card
                        title="Feed type details"
                        subtitle="Fields marked with * are required."
                    >
                        <FeedTypeForm
                            type={type}
                            action={routes["feed.types.update"]?.replace(
                                "{feedType}",
                                type.id,
                            )}
                            method="put"
                        />
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card title="Stock &amp; usage">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Current stock</dt>
                                <dd className="font-medium text-text">
                                    {`${num(currentStockKg, 3)} kg`}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Stock records</dt>
                                <dd className="font-medium text-text">
                                    {records}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Status</dt>
                                <dd>
                                    {type.is_active ? (
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

                    {records > 0 && (
                        <div className="surface-card border border-warning/40 p-4 text-sm text-text-soft">
                            This type is used by{" "}
                            <strong className="text-text">
                                {records} record(s)
                            </strong>{" "}
                            and cannot be deleted. Mark it inactive instead.
                        </div>
                    )}

                    {can("feed.type.manage") && records === 0 && (
                        <Card title="Danger zone" tone="danger">
                            <p className="text-sm text-muted">
                                This type is not used by any record, so it can
                                be removed.
                            </p>
                            <div className="mt-3">
                                <Button
                                    type="button"
                                    variant="danger"
                                    size="sm"
                                    onClick={destroy}
                                >
                                    Delete type
                                </Button>
                            </div>
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}

export default withLayout(FeedTypesEdit, "Edit Food Type");
