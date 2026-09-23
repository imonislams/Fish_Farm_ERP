import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, num, date, usePermission } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge, KpiCard } from "../../../Components/Card";
import { DataTable } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, DatePicker } from "../../../Components/Form";
import { useConfirm } from "../../../Components/ConfirmModal";

/**
 * Feeding board — today's meal schedule (plans) and recently recorded feedings.
 *
 * Creating a schedule never moves stock; only a recorded feeding does, and that
 * goes through the Food Usage / stock system.
 */
function FeedingsIndex({
    date: boardDate = "",
    board = [],
    dueCount = 0,
    recent = [],
    filters = {},
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();
    const fmtDate = date;

    const [day, setDay] = React.useState(filters.date || boardDate || "");
    const [busy, setBusy] = React.useState(false);

    const applyDate = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["feed.feedings.index"],
            { date: day },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const doneCount = board.filter((b) => b.done).length;

    const boardColumns = [
        {
            key: "time",
            label: "Time",
            render: (r) => (
                <span className="whitespace-nowrap font-medium text-text">
                    {r.time}
                </span>
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
            render: (r) => <span>{num(r.planned_kg, 3)} kg</span>,
        },
        {
            key: "recurrence",
            label: "Recurrence",
            render: (r) => <span className="text-muted">{r.recurrence}</span>,
        },
        {
            key: "done",
            label: "Status",
            align: "center",
            render: (r) =>
                r.done ? (
                    <Badge tone="success" dot>
                        Done
                    </Badge>
                ) : (
                    <Badge tone="warning" dot>
                        Due
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
                    {r.urls?.pond && (
                        <Button href={r.urls.pond} variant="ghost" size="sm">
                            Pond
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    const recentColumns = [
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
        { key: "pond", label: "Pond", render: (r) => r.pond || "—" },
        { key: "feed", label: "Feed", render: (r) => r.feed || "—" },
        {
            key: "consumed",
            label: "Consumed",
            align: "right",
            render: (r) => <span>{num(r.consumed, 3)} kg</span>,
        },
        {
            key: "status",
            label: "Status",
            align: "center",
            render: (r) => <Badge tone={r.status_tone}>{r.status}</Badge>,
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {can("feed.feeding") && r.urls?.destroy && (
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={async () => {
                                if (
                                    !(await confirm({
                                        title: "Confirm",
                                        description:
                                            "Delete this feeding record? The feed it consumed is restored to stock.",
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
            <Head title="Feeding" />

            <PageHeader
                title="Feeding"
                subtitle="Scheduled meals for the day, and the feedings actually recorded."
                breadcrumb={[
                    { label: "Food Management" },
                    { label: "Feeding" },
                ]}
                actions={
                    <>
                        {routes["feed.schedules.index"] && (
                            <Button
                                href={routes["feed.schedules.index"]}
                                variant="outline"
                                icon="calendar"
                            >
                                Schedules
                            </Button>
                        )}
                        {can("feed.schedule.manage") &&
                            routes["feed.schedules.create"] && (
                                <Button
                                    href={routes["feed.schedules.create"]}
                                    variant="outline"
                                    icon="plus"
                                >
                                    New Schedule
                                </Button>
                            )}
                        {can("feed.feeding") &&
                            routes["feed.feedings.create"] && (
                                <Button
                                    href={routes["feed.feedings.create"]}
                                    variant="secondary"
                                    icon="feed"
                                >
                                    Record Feeding
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <KpiCard
                    label="Scheduled today"
                    value={String(board.length)}
                    icon="calendar"
                    tone="primary"
                    hint="Meals planned for this day"
                />
                <KpiCard
                    label="Completed"
                    value={String(doneCount)}
                    icon="check"
                    tone="success"
                    hint="Meals recorded as fed"
                />
                <KpiCard
                    label="Still due"
                    value={String(dueCount)}
                    icon="alert"
                    tone={dueCount > 0 ? "warning" : "default"}
                    hint="Not yet recorded today"
                />
            </div>

            <div className="mt-5">
                <Card title="Day">
                    <form
                        onSubmit={applyDate}
                        className="flex flex-wrap items-end gap-3"
                    >
                        <Field label="Schedule date" name="date">
                            <DatePicker
                                name="date"
                                value={day}
                                onChange={(e) => setDay(e.target.value)}
                            />
                        </Field>
                        <Button type="submit" variant="primary" loading={busy}>
                            View
                        </Button>
                    </form>
                </Card>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Today's meal schedule"
                    actions={<Badge tone="info">{board.length} meals</Badge>}
                >
                    <DataTable
                        columns={boardColumns}
                        rows={board}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    Nothing scheduled
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Add a feeding schedule to plan this pond's
                                    meals.
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Recently recorded feedings"
                    actions={<Badge tone="info">{recent.length}</Badge>}
                >
                    <DataTable
                        columns={recentColumns}
                        rows={recent}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No feedings recorded yet
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Recording a feeding reduces the feed type's
                                    stock and feeds the FCR figures.
                                </p>
                            </div>
                        }
                    />
                </Card>
            </div>
        </>
    );
}

export default withLayout(FeedingsIndex, "Feeding");
