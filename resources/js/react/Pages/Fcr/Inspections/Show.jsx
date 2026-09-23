import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, date, usePermission } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import Button from "../../../Components/Button";

/** Inspection details — the readings taken and the health conclusion reached. */
function InspectionsShow({ inspection, schedule = null }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const readings = inspection.readings || [];

    return (
        <>
            <Head title={`Inspection — ${inspection.pond || ""}`} />

            <PageHeader
                title={`Inspection — ${date(inspection.inspected_on)}`}
                subtitle={`${inspection.pond || "—"} · ${inspection.pond_number || "—"}`}
                breadcrumb={[
                    { label: "FCR & Growth" },
                    {
                        label: "Pond Inspection",
                        href: routes["fcr.inspections.index"],
                    },
                    { label: "Details" },
                ]}
                actions={
                    <>
                        {can("fcr.inspection.update") &&
                            routes["fcr.inspections.edit"] && (
                                <Button
                                    href={routes[
                                        "fcr.inspections.edit"
                                    ]?.replace("{inspection}", inspection.id)}
                                    variant="secondary"
                                    icon="cog"
                                >
                                    Edit inspection
                                </Button>
                            )}
                        {routes["fcr.inspections.index"] && (
                            <Button
                                href={routes["fcr.inspections.index"]}
                                variant="outline"
                            >
                                All inspections
                            </Button>
                        )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <Card
                        title="Water readings"
                        subtitle="Only the parameters actually measured are listed."
                        actions={
                            <Badge tone={inspection.health_tone} dot>
                                {inspection.health}
                            </Badge>
                        }
                    >
                        {readings.length === 0 ? (
                            <div className="px-2 py-8 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No readings recorded
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    This inspection recorded a health status but
                                    no water-quality measurements.
                                </p>
                            </div>
                        ) : (
                            <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                                {readings.map((r) => (
                                    <div key={r.label}>
                                        <dt className="text-xs font-medium uppercase tracking-wide text-muted">
                                            {r.label}
                                        </dt>
                                        <dd className="mt-1 text-sm font-medium text-text">
                                            {r.value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        )}
                    </Card>

                    {(inspection.action_taken || inspection.note) && (
                        <div className="mt-5">
                            <Card title="Notes">
                                {inspection.action_taken && (
                                    <div>
                                        <dt className="text-xs font-medium uppercase tracking-wide text-muted">
                                            Action taken
                                        </dt>
                                        <dd className="mt-1 whitespace-pre-line text-sm text-text-soft">
                                            {inspection.action_taken}
                                        </dd>
                                    </div>
                                )}
                                {inspection.note && (
                                    <div
                                        className={
                                            inspection.action_taken
                                                ? "mt-4"
                                                : ""
                                        }
                                    >
                                        <dt className="text-xs font-medium uppercase tracking-wide text-muted">
                                            Note
                                        </dt>
                                        <dd className="mt-1 whitespace-pre-line text-sm text-text-soft">
                                            {inspection.note}
                                        </dd>
                                    </div>
                                )}
                            </Card>
                        </div>
                    )}
                </div>

                <div className="space-y-4">
                    <Card title="Record">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Pond</dt>
                                <dd className="font-medium text-text">
                                    {inspection.pond || "—"}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Date</dt>
                                <dd className="font-medium text-text">
                                    {date(inspection.inspected_on)}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Inspected by</dt>
                                <dd className="font-medium text-text">
                                    {inspection.inspected_by || "—"}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Health</dt>
                                <dd>
                                    <Badge tone={inspection.health_tone} dot>
                                        {inspection.health}
                                    </Badge>
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Recorded by</dt>
                                <dd className="font-medium text-text">
                                    {inspection.recorded_by || "System"}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Created</dt>
                                <dd className="font-medium text-text">
                                    {inspection.created_at || "—"}
                                </dd>
                            </div>
                        </dl>
                    </Card>

                    <Card title="Inspection schedule">
                        {schedule === null ? (
                            <>
                                <p className="text-sm text-muted">
                                    This pond has no inspection schedule. Set
                                    one to be reminded when it is next due.
                                </p>
                                {can("fcr.schedule.manage") &&
                                    routes["fcr.schedules.index"] && (
                                        <div className="mt-3">
                                            <Button
                                                href={
                                                    routes[
                                                        "fcr.schedules.index"
                                                    ]
                                                }
                                                variant="outline"
                                                size="sm"
                                            >
                                                Set schedule
                                            </Button>
                                        </div>
                                    )}
                            </>
                        ) : (
                            <dl className="space-y-2 text-sm">
                                <div className="flex justify-between gap-3">
                                    <dt className="text-muted">Frequency</dt>
                                    <dd className="font-medium text-text">
                                        {schedule.frequency}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-3">
                                    <dt className="text-muted">
                                        Last completed
                                    </dt>
                                    <dd className="font-medium text-text">
                                        {date(schedule.last_completed_on)}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-3">
                                    <dt className="text-muted">Next due</dt>
                                    <dd className="font-medium text-text">
                                        {date(schedule.next_due_on)}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-3">
                                    <dt className="text-muted">Status</dt>
                                    <dd>
                                        <Badge tone={schedule.status_tone} dot>
                                            {schedule.status}
                                        </Badge>
                                    </dd>
                                </div>
                            </dl>
                        )}
                    </Card>

                    <div className="surface-card border-info/30 p-4 text-xs text-text-soft">
                        A reading left blank means the parameter was{" "}
                        <strong className="text-text">not measured</strong> — it
                        is stored as empty, never as <code>0</code>.
                    </div>
                </div>
            </div>
        </>
    );
}

export default withLayout(InspectionsShow, "Inspection");
