import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, num, date, usePermission } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, Select } from "../../../Components/Form";
import { useConfirm } from "../../../Components/ConfirmModal";

/** Feeding schedules — the meal plans. A plan never moves feed stock. */
function SchedulesIndex({ schedules, options = {}, filters = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();
    const fmtDate = date;

    const [pond, setPond] = React.useState(filters.pond || "");
    const [busy, setBusy] = React.useState(false);

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["feed.schedules.index"],
            { pond },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const columns = [
        {
            key: "date",
            label: "Date",
            render: (r) => (
                <div>
                    <p>{fmtDate(r.date)}</p>
                    <p className="mt-0.5 text-xs text-muted">{r.time}</p>
                </div>
            ),
        },
        {
            key: "pond",
            label: "Pond",
            render: (r) => (
                <span>
                    {r.pond_number ? `${r.pond_number} — ` : ""}
                    {r.pond || "—"}
                </span>
            ),
        },
        { key: "feed", label: "Feed", render: (r) => r.feed || "—" },
        {
            key: "planned",
            label: "Planned",
            align: "right",
            render: (r) => <span>{num(r.planned, 3)} kg</span>,
        },
        {
            key: "recurrence",
            label: "Recurrence",
            render: (r) => <span className="text-muted">{r.recurrence}</span>,
        },
        {
            key: "recorded",
            label: "Feedings",
            align: "center",
            render: (r) => (
                <Badge tone={r.recorded_count > 0 ? "info" : "default"}>
                    {r.recorded_count}
                </Badge>
            ),
        },
        {
            key: "is_active",
            label: "Status",
            align: "center",
            render: (r) =>
                r.is_active ? (
                    <Badge tone="success" dot>
                        Active
                    </Badge>
                ) : (
                    <Badge tone="default" dot>
                        Paused
                    </Badge>
                ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {can("feed.feeding") && r.urls?.record && (
                        <Button
                            href={r.urls.record}
                            variant="primary"
                            size="sm"
                        >
                            Record
                        </Button>
                    )}
                    {can("feed.schedule.manage") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="ghost" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("feed.schedule.manage") && r.urls?.destroy && (
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={async () => {
                                if (
                                    !(await confirm({
                                        title: "Confirm",
                                        description:
                                            "Delete this feeding schedule? Recorded feedings are kept.",
                                    }))
                                )
                                    return;
                                router.delete(r.urls.destroy, {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            Delete
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Feeding Schedules" />

            <PageHeader
                title="Feeding Schedules"
                subtitle="Planned meals per pond. A schedule is a plan — it does not consume feed stock."
                breadcrumb={[
                    { label: "Food Management" },
                    { label: "Feeding", href: routes["feed.feedings.index"] },
                    { label: "Schedules" },
                ]}
                actions={
                    can("feed.schedule.manage") &&
                    routes["feed.schedules.create"] && (
                        <Button
                            href={routes["feed.schedules.create"]}
                            variant="secondary"
                            icon="plus"
                        >
                            New Schedule
                        </Button>
                    )
                }
            />

            <div className="mt-5">
                <Card title="Filters">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <Field label="Pond" name="pond">
                            <Select
                                name="pond"
                                value={pond}
                                onChange={(e) => setPond(e.target.value)}
                                placeholder="All ponds"
                                options={options.pondOptions || {}}
                            />
                        </Field>
                        <div className="flex items-end gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={busy}
                            >
                                Apply
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="All schedules"
                    actions={<Badge tone="info">{schedules.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={schedules.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No feeding schedules yet
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Plan a pond's meals to build a feeding
                                    routine.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={schedules} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(SchedulesIndex, "Feeding Schedules");
