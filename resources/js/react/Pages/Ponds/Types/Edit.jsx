import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import Button from "../../../Components/Button";
import TypeForm from "./TypeForm";

/** Edit Pond Type. */
function PondTypesEdit({ type }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const destroy = async () => {
        if (
            !(await confirm({
                title: "Confirm",
                description: `Delete pond type "${type.name}"? This cannot be undone.`,
            }))
        )
            return;
        router.delete(
            routes["ponds.types.destroy"]?.replace("{pondType}", type.id),
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <>
            <Head title={`Edit — ${type.name}`} />
            <PageHeader
                title={`Edit — ${type.name}`}
                subtitle="Update this pond type's details and availability."
                breadcrumb={[
                    { label: "Pond Management" },
                    { label: "Pond Types", href: routes["ponds.types.index"] },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <Card
                        title="Pond type details"
                        subtitle="Fields marked with * are required."
                    >
                        <TypeForm
                            type={type}
                            action={routes["ponds.types.update"]?.replace(
                                "{pondType}",
                                type.id,
                            )}
                            method="put"
                        />
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card title="Usage">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">
                                    Ponds using this type
                                </dt>
                                <dd className="font-medium text-text">
                                    {type.ponds_count}
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
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Created</dt>
                                <dd className="font-medium text-text">
                                    {type.created_at || "—"}
                                </dd>
                            </div>
                        </dl>
                    </Card>

                    {type.ponds_count > 0 && (
                        <div className="surface-card border border-warning/40 p-4 text-sm text-text-soft">
                            This type is used by{" "}
                            <strong className="text-text">
                                {type.ponds_count} pond(s)
                            </strong>{" "}
                            and cannot be deleted. Reassign those ponds to
                            another type first.
                        </div>
                    )}

                    {can("pond_type.delete") && type.ponds_count === 0 && (
                        <Card title="Danger zone" tone="danger">
                            <p className="text-sm text-muted">
                                This type is not used by any pond, so it can be
                                removed.
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

export default withLayout(PondTypesEdit, "Edit Pond Type");
