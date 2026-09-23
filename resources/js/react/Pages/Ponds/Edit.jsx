import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../Components/Page";
import { useConfirm } from "../../Components/ConfirmModal";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import Button from "../../Components/Button";
import PondForm from "./PondForm";

/** Edit Pond — the migrated edit form, with a read-only summary + danger zone. */
function PondsEdit({ pond, options = {}, deleteBlockReason = null }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const destroy = async () => {
        if (
            !(await confirm({
                title: "Confirm",
                description: `Delete pond "${pond.name}"? This cannot be undone.`,
            }))
        )
            return;
        router.delete(routes["ponds.destroy"]?.replace("{pond}", pond.id), {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`Edit — ${pond.name}`} />

            <PageHeader
                title={`Edit — ${pond.name}`}
                subtitle="Update this pond's classification, measurements and status."
                breadcrumb={[
                    { label: "Pond Management" },
                    { label: "All Ponds", href: routes["ponds.index"] },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <Card
                        title="Pond details"
                        subtitle="Fields marked with * are required."
                    >
                        <PondForm
                            pond={pond}
                            action={routes["ponds.update"]?.replace(
                                "{pond}",
                                pond.id,
                            )}
                            method="put"
                            options={options}
                        />
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card title="Summary">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Pond number</dt>
                                <dd>
                                    <code className="rounded bg-surface-muted px-1.5 py-0.5 text-xs text-text-soft">
                                        {pond.pond_number}
                                    </code>
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Current status</dt>
                                <dd>
                                    <Badge tone={pond.status_tone} dot>
                                        {pond.status_label}
                                    </Badge>
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Type</dt>
                                <dd className="font-medium text-text">
                                    {pond.type || "—"}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Size</dt>
                                <dd className="font-medium text-text">
                                    {pond.size}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Created</dt>
                                <dd className="font-medium text-text">
                                    {pond.created_at || "—"}
                                </dd>
                            </div>
                        </dl>
                    </Card>

                    {can("pond.delete") && (
                        <Card title="Danger zone" tone="danger">
                            {deleteBlockReason ? (
                                <>
                                    <p className="text-sm text-muted">
                                        {deleteBlockReason}
                                    </p>
                                    {routes["fish.stockings.index"] && (
                                        <div className="mt-3">
                                            <Button
                                                href={`${routes["fish.stockings.index"]}?pond=${pond.id}`}
                                                variant="outline"
                                                size="sm"
                                            >
                                                View stock records
                                            </Button>
                                        </div>
                                    )}
                                </>
                            ) : (
                                <>
                                    <p className="text-sm text-muted">
                                        Deleting a pond removes the record
                                        permanently. This pond has no stock
                                        movement history, so it can be removed.
                                    </p>
                                    <div className="mt-3">
                                        <Button
                                            type="button"
                                            variant="danger"
                                            size="sm"
                                            onClick={destroy}
                                        >
                                            Delete pond
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}

export default withLayout(PondsEdit, "Edit Pond");
