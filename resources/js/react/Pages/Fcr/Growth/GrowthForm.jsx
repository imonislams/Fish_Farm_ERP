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

/** GrowthForm — shared create/edit form for growth samples. */
export default function GrowthForm({
    record = null,
    pondOptions = {},
    action,
    method = "post",
    defaultDate = "",
    selectedPondId = null,
}) {
    const isEdit = method === "put";

    const { data, setData, post, put, processing, errors } = useForm({
        pond_id: record?.pond_id ?? selectedPondId ?? "",
        sampled_on: record?.sampled_on ?? defaultDate,
        avg_weight_g: record?.avg_weight_g ?? "",
        sample_size: record?.sample_size ?? "",
        note: record?.note ?? "",
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(action);
        } else {
            post(action);
        }
    };

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
                    label="Sampling date"
                    name="sampled_on"
                    required
                    error={errors.sampled_on}
                >
                    <DatePicker
                        name="sampled_on"
                        value={data.sampled_on}
                        onChange={(e) => setData("sampled_on", e.target.value)}
                    />
                </Field>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field
                    label="Average weight (g)"
                    name="avg_weight_g"
                    required
                    hint="Average weight of ONE fish, in grams. Required."
                    error={errors.avg_weight_g}
                >
                    <Input
                        name="avg_weight_g"
                        type="number"
                        step="0.01"
                        min="0.01"
                        inputMode="decimal"
                        value={data.avg_weight_g}
                        onChange={(e) =>
                            setData("avg_weight_g", e.target.value)
                        }
                    />
                </Field>

                <Field
                    label="Sample size"
                    name="sample_size"
                    hint="Optional. How many fish were weighed."
                    error={errors.sample_size}
                >
                    <Input
                        name="sample_size"
                        type="number"
                        step="1"
                        min="1"
                        inputMode="numeric"
                        value={data.sample_size}
                        onChange={(e) => setData("sample_size", e.target.value)}
                    />
                </Field>
            </div>

            <Field
                label="Note"
                name="note"
                hint="Optional notes about this sample."
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
                    {isEdit ? "Save changes" : "Record sample"}
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
