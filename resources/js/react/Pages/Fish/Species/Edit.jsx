import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import Button from "../../../Components/Button";
import SpeciesForm from "./SpeciesForm";

/** Edit Fish Species. */
function FishSpeciesEdit({ species }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const records =
        (species.stockings_count || 0) + (species.harvests_count || 0);

    const destroy = async () => {
        if (
            !(await confirm({
                title: "Confirm",
                description: `Delete species "${species.name}"? This cannot be undone.`,
            }))
        )
            return;
        router.delete(
            routes["fish.species.destroy"]?.replace("{species}", species.id),
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Edit — ${species.name}`} />
            <PageHeader
                title={`Edit — ${species.name}`}
                subtitle="Update this species' details and availability."
                breadcrumb={[
                    { label: "Fish Stock" },
                    {
                        label: "Fish Species",
                        href: routes["fish.species.index"],
                    },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <Card
                        title="Species details"
                        subtitle="Fields marked with * are required."
                    >
                        <SpeciesForm
                            species={species}
                            action={routes["fish.species.update"]?.replace(
                                "{species}",
                                species.id,
                            )}
                            method="put"
                        />
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card title="Usage">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Stocking records</dt>
                                <dd className="font-medium text-text">
                                    {species.stockings_count}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Harvest records</dt>
                                <dd className="font-medium text-text">
                                    {species.harvests_count}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Status</dt>
                                <dd>
                                    {species.is_active ? (
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
                                    {species.created_at || "—"}
                                </dd>
                            </div>
                        </dl>
                    </Card>

                    {records > 0 && (
                        <div className="surface-card border border-warning/40 p-4 text-sm text-text-soft">
                            This species is used by{" "}
                            <strong className="text-text">
                                {records} record(s)
                            </strong>{" "}
                            and cannot be deleted. Mark it inactive instead.
                        </div>
                    )}

                    {can("fish.species.manage") && records === 0 && (
                        <Card title="Danger zone" tone="danger">
                            <p className="text-sm text-muted">
                                This species is not used by any record, so it
                                can be removed.
                            </p>
                            <div className="mt-3">
                                <Button
                                    type="button"
                                    variant="danger"
                                    size="sm"
                                    onClick={destroy}
                                >
                                    Delete species
                                </Button>
                            </div>
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}

export default withLayout(FishSpeciesEdit, "Edit Fish Species");
