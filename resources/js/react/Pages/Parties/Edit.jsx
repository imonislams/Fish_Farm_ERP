import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money, usePermission } from "../../Components/Page";
import { useConfirm } from "../../Components/ConfirmModal";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import Button from "../../Components/Button";
import PartyForm from "./PartyForm";

/** Edit Party. */
function PartiesEdit({ party, typeOptions = {}, balance = 0 }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const destroy = async () => {
        if (
            !(await confirm({
                title: "Confirm",
                description: `Delete party "${party.name}"? This cannot be undone.`,
            }))
        )
            return;
        router.delete(routes["parties.destroy"]?.replace("{party}", party.id), {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`Edit — ${party.name}`} />
            <PageHeader
                title={`Edit — ${party.name}`}
                subtitle="Update this party's details and status."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    { label: "Parties", href: routes["parties.index"] },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <Card
                        title="Party details"
                        subtitle="Fields marked with * are required."
                    >
                        <PartyForm
                            party={party}
                            typeOptions={typeOptions}
                            action={routes["parties.update"]?.replace(
                                "{party}",
                                party.id,
                            )}
                            method="put"
                        />
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card title="Account">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Current balance</dt>
                                <dd>
                                    <Badge
                                        tone={
                                            balance > 0
                                                ? "warning"
                                                : balance < 0
                                                  ? "info"
                                                  : "default"
                                        }
                                    >
                                        {money(balance)}
                                    </Badge>
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Transactions</dt>
                                <dd className="font-medium text-text">
                                    {party.transactions_count}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Status</dt>
                                <dd>
                                    {party.is_active ? (
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

                    {!party.deletable ? (
                        <div className="surface-card border border-warning/40 p-4 text-sm text-text-soft">
                            This party has transactions and cannot be deleted.
                            Mark them inactive instead.
                        </div>
                    ) : (
                        can("party.delete") && (
                            <Card title="Danger zone" tone="danger">
                                <p className="text-sm text-muted">
                                    This party has no transactions, so they can
                                    be removed.
                                </p>
                                <div className="mt-3">
                                    <Button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        onClick={destroy}
                                    >
                                        Delete party
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

export default withLayout(PartiesEdit, "Edit Party");
