import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout, date } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import Button from "../../../Components/Button";
import { Field, Select, DatePicker } from "../../../Components/Form";

/** Edit Schedule — change how often a pond is inspected. */
function SchedulesEdit({ schedule, frequencyOptions = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};

    const { data, setData, put, processing, errors } = useForm({
        pond_id: schedule.pond_id,
        frequency: schedule.frequency ?? "",
        last_completed_on: schedule.last_completed_on ?? "",
        is_active: !!schedule.is_active,
    });

    const submit = (e) => {
        e.preventDefault();
        put(
            routes["fcr.schedules.update"]?.replace("{schedule}", schedule.id),
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Edit Schedule — ${schedule.pond || ""}`} />
            <PageHeader
                title={`Edit Schedule — ${schedule.pond || ""}`}
                subtitle="Change how often this pond is inspected. The next due date is recalculated automatically."
                breadcrumb={[
                    { label: "FCR & Growth" },
                    {
                        label: "Inspection Schedule",
                        href: routes["fcr.schedules.index"],
                    },
                    { label: "Edit" },
                ]}
            />

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <form onSubmit={submit}>
                        <Card
                            title="Schedule details"
                            subtitle="Fields marked with * are required."
                        >
                            <div className="space-y-5">
                                <Field
                                    label="Pond"
                                    name="pond_id"
                                    required
                                    hint="A schedule belongs to one pond."
                                    error={errors.pond_id}
                                >
                                    <Select
                                        name="pond_id"
                                        value={data.pond_id}
                                        disabled
                                        options={{
                                            [schedule.pond_id]:
                                                schedule.pond_label || "",
                                        }}
                                    />
                                </Field>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field
                                        label="Frequency"
                                        name="frequency"
                                        required
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
                                            options={frequencyOptions}
                                        />
                                    </Field>

                                    <Field
                                        label="Last completed"
                                        name="last_completed_on"
                                        hint="Optional. Adjusting this moves the next due date."
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
                                </div>

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
                            </div>
                        </Card>

                        <div className="mt-4 flex flex-wrap items-center gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={processing}
                            >
                                Save changes
                            </Button>
                            {routes["fcr.schedules.index"] && (
                                <Button
                                    href={routes["fcr.schedules.index"]}
                                    variant="outline"
                                >
                                    Cancel
                                </Button>
                            )}
                        </div>
                    </form>
                </div>

                <div className="space-y-4">
                    <Card title="Current state">
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Frequency</dt>
                                <dd className="font-medium text-text">
                                    {schedule.frequency_label}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Next due</dt>
                                <dd className="font-medium text-text">
                                    {schedule.next_due_on
                                        ? date(schedule.next_due_on)
                                        : "—"}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">Status</dt>
                                <dd>
                                    <Badge tone={schedule.status_tone} dot>
                                        {schedule.status_label}
                                    </Badge>
                                </dd>
                            </div>
                        </dl>
                    </Card>

                    <div className="surface-card border-info/30 p-4 text-xs text-text-soft">
                        Recording an inspection automatically completes this
                        schedule and pushes the next due date forward — you
                        never set the due date by hand.
                    </div>
                </div>
            </div>
        </>
    );
}

export default withLayout(SchedulesEdit, "Edit Schedule");
