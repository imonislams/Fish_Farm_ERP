import React from "react";
import { useForm } from "@inertiajs/react";
import { Field, Input, Textarea } from "../../Components/Form";
import Button from "../../Components/Button";

/** CustomerForm — shared create/edit form (React mirror of customers/_form). */
export default function CustomerForm({
    customer = null,
    action,
    method = "post",
}) {
    const { data, setData, post, put, processing, errors, transform } = useForm(
        {
            name: customer?.name ?? "",
            phone: customer?.phone ?? "",
            email: customer?.email ?? "",
            address: customer?.address ?? "",
            opening_balance: customer?.opening_balance ?? 0,
            credit_limit: customer?.credit_limit ?? "",
            note: customer?.note ?? "",
            is_active: customer ? !!customer.is_active : true,
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
                    label="Customer name"
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

                <Field label="Phone" name="phone" error={errors.phone}>
                    <Input
                        name="phone"
                        value={data.phone}
                        onChange={(e) => setData("phone", e.target.value)}
                    />
                </Field>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field label="Email" name="email" error={errors.email}>
                    <Input
                        name="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData("email", e.target.value)}
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

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field
                    label="Opening balance"
                    name="opening_balance"
                    hint="Money owed before this system. Positive = they owe the farm."
                    error={errors.opening_balance}
                >
                    <Input
                        name="opening_balance"
                        type="number"
                        step="0.01"
                        inputMode="decimal"
                        value={data.opening_balance}
                        onChange={(e) =>
                            setData("opening_balance", e.target.value)
                        }
                    />
                </Field>

                <Field
                    label="Credit limit"
                    name="credit_limit"
                    hint="Optional. Maximum credit allowed."
                    error={errors.credit_limit}
                >
                    <Input
                        name="credit_limit"
                        type="number"
                        step="0.01"
                        min="0"
                        inputMode="decimal"
                        value={data.credit_limit}
                        onChange={(e) =>
                            setData("credit_limit", e.target.value)
                        }
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

            <Field label="Status" name="is_active" error={errors.is_active}>
                <label className="flex items-center gap-2 text-sm text-text-soft">
                    <input
                        type="checkbox"
                        checked={data.is_active}
                        onChange={(e) => setData("is_active", e.target.checked)}
                        className="h-4 w-4 rounded border-border-strong text-primary focus:ring-primary/40"
                    />
                    <span>Active — available for new sales</span>
                </label>
            </Field>

            <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {method === "put" ? "Save changes" : "Create customer"}
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
