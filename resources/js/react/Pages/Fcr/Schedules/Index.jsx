import React from "react";
import { Head, Link, router, useForm, usePage } from "@inertiajs/react";
import { withLayout, num, date, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, Select, DatePicker } from "../../../Components/Form";
import Icon from "../../../Components/Icon";

const STATUS_META = (dueSoonDays) => ({
    overdue: {
        label: "Overdue",
        tone: "danger",
        note: "Past the due date",
    },
    due_soon: {
        label: "Due soon",
        tone: "warning",
        note: `Within ${dueSoonDays} days`,
    },
    scheduled: {
        label: "Scheduled",
        tone: "success",
        note: "Nothing due yet",
    },
    inactive: {
        label: "Inactive",
        tone: "default",
        note: "Schedule paused",
    },
});

/** Inspection Schedule — how often each pond should be inspected. */
function SchedulesIndex({
    schedules = [],
    statusCounts = {},
    status = "",
    pondsWithoutSchedule = [],
    options = {},
    frequencyOptions = {},
    dueSoonDays = 2,
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const meta = STATUS_META(dueSoonDays);

    const { data, setData, post, processing, errors, reset } = useForm({
        pond_id: "",
        frequency: "",
        last_completed_on: "",
        is_active: true,
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["fcr.schedules.store"], {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const cardHref = (key) => {
        const base = routes["fcr.schedules.index"] || window.location.pathname;
        return status === key ? base : `${base}?status=${key}`;
    };

    const columns = [
        {
            key: "pond",
            label: "Pond",
            render: (r) => (
                <div>
                    <span className="font-medium text-text">{r.pond}</span>
                    {r.pond_number && (
                        <p className="mt-0.5 text-xs text-muted">
                            <code className="rounded bg-surface-muted px-1.5 py-0.5">
                                {r.pond_number}
                            </code>
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: "frequency",
            label: "Frequency",
            render: (r) => (
                <span className="text-text-soft">{r.frequency}</span>
            ),
        },
        {
            key: "last_done",
            label: "Last done",
            align: "right",
            render: (r) => (
                <span className="whitespace-nowrap text-text-soft">
                    {r.last_completed_on ? date(r.last_completed_on) : "—"}
                </span>
            ),
        },
        {
            key: "next_due",
            label: "Next due",
            align: "right",
            render: (r) => (
                <div>
                    <span className="whitespace-nowrap text-text-soft">
                        {r.next_due_on ? date(r.next_due_on) : "—"}
                    </span>
                    {r.due_text && (
                        <p
                            className={`mt-0.5 text-xs ${
                                r.days_until_due < 0
                                    ? "text-danger"
                                    : "text-muted"
                            }`}
                        >
                            {r.due_text}
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: "status",
            label: "Status",
            align: "center",
            render: (r) => (
                <Badge tone={r.status_tone} dot>
                    {r.status}
                </Badge>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {routes["fcr.inspections.create"] && r.pond_id && (
                        <Button
                            href={`${routes["fcr.inspections.create"]}?pond=${r.pond_id}`}
                            variant="outline"
                            size="sm"
                        >
                            Inspect
                        </Button>
                    )}
                    {can("fcr.schedule.manage") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="ghost" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("fcr.schedule.manage") && r.urls?.destroy && (
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={async () => {
                                if (
                                    !(await confirm({ title: "Confirm", description: "Remove this pond's inspection schedule? Inspections already recorded are not affected." }))
                                )
                                    return;
                                router.delete(r.urls.destroy, {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            Remove
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Inspection Schedule" />

            <PageHeader
                title="Inspection Schedule"
                subtitle="How often each pond should be inspected, and when it is next due."
                breadcrumb={[
                    { label: "FCR & Growth" },
                    { label: "Inspection Schedule" },
                ]}
                actions={
                    routes["fcr.index"] && (
                        <Button
                            href={routes["fcr.index"]}
                            variant="outline"
                            icon="chart"
                        >
                            FCR Dashboard
                        </Button>
                    )
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {Object.entries(meta).map(([key, m]) => {
                    const isSelected = status === key;
                    return (
                        <Link
                            key={key}
                            href={cardHref(key)}
                            className={`surface-card surface-card--hoverable block border p-4 transition-colors ${
                                isSelected
                                    ? "border-primary ring-1 ring-primary/30"
                                    : ""
                            }`}
                            aria-current={isSelected ? "true" : undefined}
                        >
                            <div className="flex items-center justify-between gap-3">
                                <span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-surface-muted text-muted">
                                    <Icon name="search" className="h-5 w-5" />
                                </span>
                                <Badge tone={m.tone}>{m.label}</Badge>
                            </div>
                            <p className="mt-3 text-2xl font-bold text-text">
                                {num(statusCounts[key] ?? 0, 0)}
                            </p>
                            <p className="mt-0.5 text-xs text-muted">
                                {m.note}
                            </p>
                        </Link>
                    );
                })}
            </div>

            {pondsWithoutSchedule.length > 0 && (
                <div className="mt-5 surface-card border-warning/40 p-4 text-sm text-text-soft">
                    <strong className="text-text">
                        {pondsWithoutSchedule.length} pond(s) have no inspection
                        schedule:
                    </strong>{" "}
                    {pondsWithoutSchedule.join(", ")}. Set a schedule below so
                    inspections are tracked.
                </div>
            )}

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                {can("fcr.schedule.manage") && (
                    <div className="lg:col-span-1">
                        <Card
                            title="Set schedule"
                            subtitle="Creates or replaces a pond's schedule. The due date is calculated automatically."
                        >
                            {Object.keys(options.pondOptions || {}).length ===
                            0 ? (
                                <div className="surface-card border-warning/40 p-3 text-sm text-text-soft">
                                    No ponds exist yet.
                                </div>
                            ) : (
                                <form onSubmit={submit} className="space-y-4">
                                    <Field
                                        label="Pond"
                                        name="pond_id"
                                        required
                                        error={errors.pond_id}
                                    >
                                        <Select
                                            name="pond_id"
                                            value={data.pond_id}
                                            onChange={(e) =>
                                                setData(
                                                    "pond_id",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Select a pond…"
                                            options={options.pondOptions || {}}
                                        />
                                    </Field>

                                    <Field
                                        label="Frequency"
                                        name="frequency"
                                        required
                                        hint="How often this pond should be inspected."
                                        error={errors.frequency}
                                    >
                                        <Select
                                            name="frequency"
                                            value={data.frequency}
                                            onChange={(e) =>
                                                setData(
                                                    "frequency",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Select a frequency…"
                                            options={frequencyOptions}
                                        />
                                    </Field>

                                    <Field
                                        label="Last completed"
                                        name="last_completed_on"
                                        hint="Optional. When it was last inspected. Leave blank if never."
                                        error={errors.last_completed_on}
                                    >
                                        <DatePicker
                                            name="last_completed_on"
                                            value={data.last_completed_on}
                                            onChange={(e) =>
                                                setData(
                                                    "last_completed_on",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Active"
                                        name="is_active"
                                        error={errors.is_active}
                                    >
                                        <label className="flex items-center gap-2 text-sm text-text-soft">
                                            <input
                                                type="checkbox"
                                                checked={data.is_active}
                                                onChange={(e) =>
                                                    setData(
                                                        "is_active",
                                                        e.target.checked,
                                                    )
                                                }
                                                className="h-4 w-4 rounded border-border-strong text-primary focus:ring-primary/40"
                                            />
                                            <span>Track this schedule</span>
                                        </label>
                                    </Field>

                                    <Button
                                        type="submit"
                                        variant="primary"
                                        loading={processing}
                                        className="w-full"
                                    >
                                        Save schedule
                                    </Button>
                                </form>
                            )}
                        </Card>
                    </div>
                )}

                <div
                    className={
                        can("fcr.schedule.manage")
                            ? "lg:col-span-2"
                            : "lg:col-span-3"
                    }
                >
                    <Card
                        padded={false}
                        title="Schedule list"
                        actions={
                            <>
                                <Badge tone="info">
                                    {schedules.length} total
                                </Badge>
                                {status !== "" &&
                                    routes["fcr.schedules.index"] && (
                                        <Button
                                            href={routes["fcr.schedules.index"]}
                                            variant="ghost"
                                            size="sm"
                                        >
                                            Clear filter
                                        </Button>
                                    )}
                            </>
                        }
                    >
                        <DataTable
                            columns={columns}
                            rows={schedules}
                            empty={
                                <div className="px-6 py-12 text-center">
                                    <h4 className="text-sm font-semibold text-text">
                                        {status !== ""
                                            ? "No schedules in this state"
                                            : "No schedules yet"}
                                    </h4>
                                    <p className="mt-1 text-sm text-muted">
                                        {status !== ""
                                            ? "No pond's schedule is currently in that state. Clear the filter to see them all."
                                            : "Set a schedule for a pond to start tracking its inspections."}
                                    </p>
                                </div>
                            }
                        />
                    </Card>
                </div>
            </div>
        </>
    );
}

export default withLayout(SchedulesIndex, "Inspection Schedule");
