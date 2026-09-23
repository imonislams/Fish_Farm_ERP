import React from "react";
import { useForm } from "@inertiajs/react";
import {
    Field,
    Input,
    Select,
    DatePicker,
    Textarea,
} from "../../../Components/Form";
import Button from "../../../Components/Button";

/**
 * InspectionForm — shared create/edit form.
 *
 * Measurements are optional; a blank reading is stored as NULL, never 0, so
 * "not measured" and "measured zero" stay distinguishable.
 */
export default function InspectionForm({
    inspection = null,
    pondOptions = {},
    statusOptions = {},
    parameters = {},
    action,
    method = "post",
    selectedPondId = null,
    defaultDate = "",
}) {
    const isEdit = method === "put";

    const initial = {
        pond_id: inspection?.pond_id ?? selectedPondId ?? "",
        inspected_on: inspection?.inspected_on ?? defaultDate,
        health_status: inspection?.health_status ?? "",
        inspected_by: inspection?.inspected_by ?? "",
        action_taken: inspection?.action_taken ?? "",
        note: inspection?.note ?? "",
    };
    for (const key of Object.keys(parameters)) {
        initial[key] = inspection?.readings?.[key] ?? "";
    }

    const { data, setData, post, put, processing, errors } = useForm(initial);

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(action);
        } else {
            post(action);
        }
    };

    const step = (decimals) =>
        decimals > 0 ? "0." + "0".repeat(decimals - 1) + "1" : "1";

    return (
        <form onSubmit={submit} className="space-y-5">
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
                        onChange={(e) => setData("pond_id", e.target.value)}
                        placeholder="Select a pond…"
                        options={pondOptions}
                    />
                </Field>

                <Field
                    label="Inspection date"
                    name="inspected_on"
                    required
                    error={errors.inspected_on}
                >
                    <DatePicker
                        name="inspected_on"
                        value={data.inspected_on}
                        onChange={(e) =>
                            setData("inspected_on", e.target.value)
                        }
                    />
                </Field>
            </div>

            <div>
                <h3 className="text-sm font-semibold text-text">
                    Water readings
                </h3>
                <p className="mt-0.5 text-xs text-muted">
                    All optional — leave a field blank if it was not measured.
                </p>

                <div className="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {Object.entries(parameters).map(([key, meta]) => (
                        <Field
                            key={key}
                            label={meta.label}
                            name={key}
                            hint={`Range ${meta.min}–${meta.max}`}
                            error={errors[key]}
                        >
                            <Input
                                name={key}
                                type="number"
                                step={step(meta.decimals)}
                                min={meta.min}
                                max={meta.max}
                                inputMode="decimal"
                                value={data[key] ?? ""}
                                onChange={(e) => setData(key, e.target.value)}
                            />
                        </Field>
                    ))}
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field
                    label="Health status"
                    name="health_status"
                    required
                    hint="The conclusion reached."
                    error={errors.health_status}
                >
                    <Select
                        name="health_status"
                        value={data.health_status}
                        onChange={(e) =>
                            setData("health_status", e.target.value)
                        }
                        placeholder="Select…"
                        options={statusOptions}
                    />
                </Field>

                <Field
                    label="Inspected by"
                    name="inspected_by"
                    hint="Optional. Who carried out the inspection."
                    error={errors.inspected_by}
                >
                    <Input
                        name="inspected_by"
                        value={data.inspected_by}
                        onChange={(e) =>
                            setData("inspected_by", e.target.value)
                        }
                    />
                </Field>
            </div>

            <Field
                label="Action taken"
                name="action_taken"
                hint="Optional. What was done as a result."
                error={errors.action_taken}
            >
                <Textarea
                    name="action_taken"
                    rows={3}
                    value={data.action_taken}
                    onChange={(e) => setData("action_taken", e.target.value)}
                />
            </Field>

            <Field
                label="Note"
                name="note"
                hint="Optional further notes."
                error={errors.note}
            >
                <Textarea
                    name="note"
                    rows={3}
                    value={data.note}
                    onChange={(e) => setData("note", e.target.value)}
                />
            </Field>

            <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {isEdit ? "Save changes" : "Record inspection"}
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
