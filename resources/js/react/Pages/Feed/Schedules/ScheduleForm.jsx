import React from "react";
import { useForm, usePage } from "@inertiajs/react";
import {
    Field,
    Input,
    Select,
    DatePicker,
    Textarea,
} from "../../../Components/Form";
import Button from "../../../Components/Button";
import { Card } from "../../../Components/Card";

/**
 * ScheduleForm — shared by the create and edit feeding-schedule pages.
 *
 * A schedule is a PLAN. It does not move feed stock; the help text says so, and
 * the backend enforces it.
 */
export default function ScheduleForm({
    schedule = {},
    options = {},
    mode = "create",
    defaultDate = "",
}) {
    const { props } = usePage();
    const routes = props.routes || {};

    const { data, setData, post, put, processing, errors } = useForm({
        pond_id: schedule.pond_id ?? "",
        feed_type_id: schedule.feed_type_id ?? "",
        scheduled_on: schedule.scheduled_on ?? defaultDate,
        scheduled_time: schedule.scheduled_time ?? "06:00",
        planned_quantity_kg: schedule.planned_quantity_kg ?? "",
        recurrence: schedule.recurrence ?? "once",
        is_active: schedule.is_active ?? true,
        note: schedule.note ?? "",
    });

    const submit = (e) => {
        e.preventDefault();
        if (mode === "edit") {
            put(
                routes["feed.schedules.update"].replace(
                    "{schedule}",
                    schedule.id,
                ),
            );
        } else {
            post(routes["feed.schedules.store"]);
        }
    };

    return (
        <form onSubmit={submit}>
            <Card
                title="Schedule details"
                subtitle="Fields marked with * are required."
            >
                <div className="flex items-start gap-2 rounded-control border border-info/30 bg-info/5 p-3 text-sm text-text-soft">
                    <span className="mt-0.5">ℹ</span>
                    <p>
                        A schedule is a plan only. Feed stock is reduced when a
                        feeding is actually recorded, not when it is scheduled.
                    </p>
                </div>

                <div className="mt-5 space-y-5">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                                    setData("pond_id", e.target.value)
                                }
                                placeholder="Select a pond…"
                                options={options.pondOptions || {}}
                            />
                        </Field>

                        <Field
                            label="Feed type"
                            name="feed_type_id"
                            required
                            error={errors.feed_type_id}
                        >
                            <Select
                                name="feed_type_id"
                                value={data.feed_type_id}
                                onChange={(e) =>
                                    setData("feed_type_id", e.target.value)
                                }
                                placeholder="Select a feed type…"
                                options={options.typeOptions || {}}
                            />
                        </Field>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <Field
                            label="Date"
                            name="scheduled_on"
                            required
                            error={errors.scheduled_on}
                        >
                            <DatePicker
                                name="scheduled_on"
                                value={data.scheduled_on}
                                onChange={(e) =>
                                    setData("scheduled_on", e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="Time"
                            name="scheduled_time"
                            required
                            error={errors.scheduled_time}
                        >
                            <Input
                                name="scheduled_time"
                                type="time"
                                value={data.scheduled_time}
                                onChange={(e) =>
                                    setData("scheduled_time", e.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label="Planned (kg)"
                            name="planned_quantity_kg"
                            required
                            error={errors.planned_quantity_kg}
                        >
                            <Input
                                name="planned_quantity_kg"
                                type="number"
                                step="0.001"
                                min="0.001"
                                inputMode="decimal"
                                value={data.planned_quantity_kg}
                                onChange={(e) =>
                                    setData(
                                        "planned_quantity_kg",
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <Field
                            label="Recurrence"
                            name="recurrence"
                            required
                            error={errors.recurrence}
                        >
                            <Select
                                name="recurrence"
                                value={data.recurrence}
                                onChange={(e) =>
                                    setData("recurrence", e.target.value)
                                }
                                options={options.recurrenceOptions || {}}
                            />
                        </Field>

                        <Field label="Status" name="is_active">
                            <Select
                                name="is_active"
                                value={data.is_active ? "1" : "0"}
                                onChange={(e) =>
                                    setData("is_active", e.target.value === "1")
                                }
                                options={{ 1: "Active", 0: "Paused" }}
                            />
                        </Field>
                    </div>

                    <Field label="Note" name="note" error={errors.note}>
                        <Textarea
                            name="note"
                            rows={3}
                            value={data.note}
                            onChange={(e) => setData("note", e.target.value)}
                        />
                    </Field>
                </div>
            </Card>

            <div className="mt-4 flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {mode === "edit" ? "Save changes" : "Create schedule"}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => window.history.back()}
                    disabled={processing}
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}
