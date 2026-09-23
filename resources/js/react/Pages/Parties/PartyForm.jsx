import React from "react";
import { useForm } from "@inertiajs/react";
import { Field, Input, Select, Textarea } from "../../Components/Form";
import Button from "../../Components/Button";

/** PartyForm — shared create/edit form (React mirror of parties/_form). */
export default function PartyForm({
    party = null,
    typeOptions = {},
    action,
    method = "post",
}) {
    const { data, setData, post, put, processing, errors, transform } = useForm(
        {
            name: party?.name ?? "",
            type: party?.type ?? "",
            phone: party?.phone ?? "",
            address: party?.address ?? "",
            opening_balance: party?.opening_balance ?? 0,
            note: party?.note ?? "",
            is_active: party ? !!party.is_active : true,
        },
    );

    const submit = (e) => {
        e.preventDefault();
        transform((d) => ({ ...d, is_active: d.is_active ? 1 : 0 }));
        if (method === "put") {
            put(action);
        } else {
            post(action);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field
                    label="Party name"
                    name="name"
                    required
                    error={errors.name}
                >
                    <Input
                        name="name"
                        value={data.name}
                        onChange={(e) => setData("name", e.target.value)}
                        autoFocus
                    />
                </Field>

                <Field label="Type" name="type" required error={errors.type}>
                    <Select
                        name="type"
                        value={data.type}
                        onChange={(e) => setData("type", e.target.value)}
                        placeholder="Select a type…"
                        options={typeOptions}
                    />
                </Field>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field label="Phone" name="phone" error={errors.phone}>
                    <Input
                        name="phone"
                        value={data.phone}
                        onChange={(e) => setData("phone", e.target.value)}
                    />
                </Field>

                <Field label="Address" name="address" error={errors.address}>
                    <Input
                        name="address"
                        value={data.address}
                        onChange={(e) => setData("address", e.target.value)}
                    />
                </Field>
            </div>

            <Field
                label="Opening balance"
                name="opening_balance"
                hint="Balance before this system. Positive = the party owes the farm."
                error={errors.opening_balance}
            >
                <Input
                    name="opening_balance"
                    type="number"
                    step="0.01"
                    inputMode="decimal"
                    value={data.opening_balance}
                    onChange={(e) => setData("opening_balance", e.target.value)}
                />
            </Field>

            <Field label="Note" name="note" error={errors.note}>
                <Textarea
                    name="note"
                    rows={3}
                    value={data.note}
                    onChange={(e) => setData("note", e.target.value)}
                />
            </Field>

            <Field label="Status" name="is_active" error={errors.is_active}>
                <label className="flex items-center gap-2 text-sm text-text-soft">
                    <input
                        type="checkbox"
                        checked={data.is_active}
                        onChange={(e) => setData("is_active", e.target.checked)}
                        className="h-4 w-4 rounded border-border-strong text-primary focus:ring-primary/40"
                    />
                    <span>Active</span>
                </label>
            </Field>

            <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {method === "put" ? "Save changes" : "Create party"}
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
