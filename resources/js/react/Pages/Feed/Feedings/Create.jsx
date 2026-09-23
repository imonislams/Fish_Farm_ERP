import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout, num } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import Button from "../../../Components/Button";
import {
    Field,
    Input,
    Select,
    DatePicker,
    Textarea,
} from "../../../Components/Form";

/**
 * Record an ACTUAL feeding.
 *
 * This is the action that consumes feed stock: submitting writes a Food Usage row
 * through FeedStockService, which reduces the feed type's stock and feeds FCR.
 * Marking the meal "skipped" consumes nothing.
 */
function FeedingsCreate({ options = {}, schedule = null, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};

    const { data, setData, post, processing, errors } = useForm({
        pond_id: schedule?.pond_id ?? "",
        feed_type_id: schedule?.feed_type_id ?? "",
        feeding_schedule_id: schedule?.id ?? "",
        planned_quantity_kg: schedule?.planned_quantity_kg ?? "",
        consumed_quantity_kg: schedule?.planned_quantity_kg ?? "",
        fed_on: schedule?.scheduled_on ?? defaultDate,
        fed_at: "",
        status: "completed",
        note: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["feed.feedings.store"]);
    };

    const statusOptions = options.statusOptions || {};
    // The catalogue keys are needed for the select values; labels come from config.
    const statusKeys = [
        { value: "completed", label: statusOptions.completed || "Completed" },
        { value: "partial", label: statusOptions.partial || "Partial" },
        { value: "skipped", label: statusOptions.skipped || "Skipped" },
    ];

    return (
        <>
            <Head title="Record Feeding" />
            <PageHeader
                title="Record Feeding"
                subtitle="Record a meal that was actually given. This reduces feed stock and feeds the FCR figures."
                breadcrumb={[
                    { label: "Food Management" },
                    { label: "Feeding", href: routes["feed.feedings.index"] },
                    { label: "Record" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                {schedule && (
                    <Card tone="info" title="From schedule">
                        <p className="text-sm text-text-soft">
                            Scheduled for <strong>{schedule.pond}</strong> —{" "}
                            <strong>{schedule.feed}</strong> (planned{" "}
                            {schedule.planned_quantity_kg} kg). Adjust the
                            consumed amount below if less was given.
                        </p>
                    </Card>
                )}

                <div className="surface-card mt-4 border border-info/30 p-4 text-sm text-text-soft">
                    Current feed stock:
                    <ul className="mt-2 space-y-0.5 text-xs">
                        {Object.entries(options.typeOptions || {}).map(
                            ([id, label]) => (
                                <li key={id}>
                                    {label}:{" "}
                                    <strong>
                                        {num(options.stockByType?.[id] ?? 0, 3)}
                                    </strong>{" "}
                                    kg
                                </li>
                            ),
                        )}
                    </ul>
                </div>

                <form onSubmit={submit} className="mt-4">
                    <Card
                        title="Feeding details"
                        subtitle="Fields marked with * are required."
                    >
                        <div className="space-y-5">
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
                                            setData(
                                                "feed_type_id",
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Select a feed type…"
                                        options={options.typeOptions || {}}
                                    />
                                </Field>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <Field
                                    label="Consumed (kg)"
                                    name="consumed_quantity_kg"
                                    required
                                    hint="Reduces the feed type's stock."
                                    error={errors.consumed_quantity_kg}
                                >
                                    <Input
                                        name="consumed_quantity_kg"
                                        type="number"
                                        step="0.001"
                                        min="0"
                                        inputMode="decimal"
                                        value={data.consumed_quantity_kg}
                                        onChange={(e) =>
                                            setData(
                                                "consumed_quantity_kg",
                                                e.target.value,
                                            )
                                        }
                                    />
                                </Field>

                                <Field
                                    label="Date"
                                    name="fed_on"
                                    required
                                    error={errors.fed_on}
                                >
                                    <DatePicker
                                        name="fed_on"
                                        value={data.fed_on}
                                        onChange={(e) =>
                                            setData("fed_on", e.target.value)
                                        }
                                    />
                                </Field>

                                <Field
                                    label="Time"
                                    name="fed_at"
                                    error={errors.fed_at}
                                >
                                    <Input
                                        name="fed_at"
                                        type="time"
                                        value={data.fed_at}
                                        onChange={(e) =>
                                            setData("fed_at", e.target.value)
                                        }
                                    />
                                </Field>
                            </div>

                            <Field
                                label="Status"
                                name="status"
                                required
                                error={errors.status}
                            >
                                <Select
                                    name="status"
                                    value={data.status}
                                    onChange={(e) =>
                                        setData("status", e.target.value)
                                    }
                                    options={statusKeys}
                                />
                            </Field>

                            <Field label="Note" name="note" error={errors.note}>
                                <Textarea
                                    name="note"
                                    rows={3}
                                    value={data.note}
                                    onChange={(e) =>
                                        setData("note", e.target.value)
                                    }
                                />
                            </Field>
                        </div>
                    </Card>

                    <div className="mt-4 flex flex-wrap items-center gap-2">
                        <Button
                            type="submit"
                            variant="primary"
                            loading={processing}
                        >
                            Record feeding
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
            </div>
        </>
    );
}

export default withLayout(FeedingsCreate, "Record Feeding");
