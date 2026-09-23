import React from "react";
import { useForm, usePage } from "@inertiajs/react";
import {
    Field,
    Input,
    Select,
    DatePicker,
    Textarea,
} from "../../Components/Form";
import Button from "../../Components/Button";
import { money } from "../../Components/Page";

/**
 * SaleForm — sale header + dynamic line items.
 *
 * Line totals and the subtotal are shown live for convenience; the SERVER
 * recalculates everything and validates each line, so this is UX, not authority.
 * A sale needs at least one line; the last line cannot be removed.
 */
export default function SaleForm({
    sale = null,
    options = {},
    action,
    method = "post",
    nextInvoiceNo = "",
    defaultDate = "",
}) {
    const isEdit = method === "put";

    const emptyLine = {
        fish_species_id: "",
        pond_id: "",
        quantity: "",
        weight_kg: "",
        unit_price: "",
        description: "",
    };

    const seedLines =
        sale?.items?.length > 0
            ? sale.items.map((i) => ({
                  fish_species_id: i.fish_species_id ?? "",
                  pond_id: i.pond_id ?? "",
                  quantity: i.quantity ?? "",
                  weight_kg: i.weight_kg ?? "",
                  unit_price: i.unit_price ?? "",
                  description: i.description ?? "",
              }))
            : [{ ...emptyLine }];

    const { data, setData, post, put, processing, errors } = useForm({
        customer_id: sale?.customer_id ?? "",
        invoice_no: sale?.invoice_no ?? nextInvoiceNo ?? "",
        sale_date: sale?.sale_date ?? defaultDate,
        discount: sale?.discount ?? 0,
        paid_amount: sale?.paid_amount ?? 0,
        note: sale?.note ?? "",
        items: seedLines,
    });

    const lineTotal = (line) => {
        const weight = parseFloat(line.weight_kg || 0) || 0;
        const qty = parseFloat(line.quantity || 0) || 0;
        const price = parseFloat(line.unit_price || 0) || 0;
        const basis = weight > 0 ? weight : qty;
        return basis * price;
    };

    const subtotal = data.items.reduce((a, l) => a + lineTotal(l), 0);

    const setLine = (index, key, value) =>
        setData(
            "items",
            data.items.map((l, i) =>
                i === index ? { ...l, [key]: value } : l,
            ),
        );

    const addLine = () => setData("items", [...data.items, { ...emptyLine }]);

    const removeLine = (index) => {
        if (data.items.length <= 1) return;
        setData(
            "items",
            data.items.filter((_, i) => i !== index),
        );
    };

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(action);
        } else {
            post(action);
        }
    };

    const cellInput =
        "w-full rounded-control border-border-strong bg-surface px-2 py-1.5 text-sm text-text";

    return (
        <form onSubmit={submit} className="space-y-5">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Field
                    label="Customer"
                    name="customer_id"
                    required
                    error={errors.customer_id}
                >
                    <Select
                        name="customer_id"
                        value={data.customer_id}
                        onChange={(e) => setData("customer_id", e.target.value)}
                        placeholder="Select a customer…"
                        options={options.customerOptions || {}}
                    />
                </Field>

                <Field
                    label="Invoice number"
                    name="invoice_no"
                    required
                    hint="Unique. Pre-filled with the next number."
                    error={errors.invoice_no}
                >
                    <Input
                        name="invoice_no"
                        value={data.invoice_no}
                        onChange={(e) => setData("invoice_no", e.target.value)}
                    />
                </Field>

                <Field
                    label="Sale date"
                    name="sale_date"
                    required
                    error={errors.sale_date}
                >
                    <DatePicker
                        name="sale_date"
                        value={data.sale_date}
                        onChange={(e) => setData("sale_date", e.target.value)}
                    />
                </Field>
            </div>

            <div>
                <div className="mb-2 flex items-center justify-between gap-3">
                    <h3 className="text-sm font-semibold text-text">
                        Sale items
                    </h3>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        icon="plus"
                        onClick={addLine}
                    >
                        Add line
                    </Button>
                </div>

                <div className="table-shell">
                    <table className="w-full text-sm">
                        <thead className="border-b border-border bg-surface-muted">
                            <tr>
                                {[
                                    "Species",
                                    "Pond",
                                    "Weight (kg)",
                                    "Qty",
                                    "Unit price",
                                    "Description",
                                ].map((h) => (
                                    <th
                                        key={h}
                                        scope="col"
                                        className="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-muted"
                                    >
                                        {h}
                                    </th>
                                ))}
                                <th className="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-muted">
                                    Line total
                                </th>
                                <th className="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {data.items.map((line, index) => (
                                <tr key={index}>
                                    <td className="px-3 py-2">
                                        <select
                                            className={cellInput}
                                            value={line.fish_species_id}
                                            onChange={(e) =>
                                                setLine(
                                                    index,
                                                    "fish_species_id",
                                                    e.target.value,
                                                )
                                            }
                                        >
                                            <option value="">—</option>
                                            {Object.entries(
                                                options.speciesOptions || {},
                                            ).map(([v, l]) => (
                                                <option key={v} value={v}>
                                                    {l}
                                                </option>
                                            ))}
                                        </select>
                                    </td>
                                    <td className="px-3 py-2">
                                        <select
                                            className={cellInput}
                                            value={line.pond_id}
                                            onChange={(e) =>
                                                setLine(
                                                    index,
                                                    "pond_id",
                                                    e.target.value,
                                                )
                                            }
                                        >
                                            <option value="">—</option>
                                            {Object.entries(
                                                options.pondOptions || {},
                                            ).map(([v, l]) => (
                                                <option key={v} value={v}>
                                                    {l}
                                                </option>
                                            ))}
                                        </select>
                                    </td>
                                    <td className="px-3 py-2">
                                        <input
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            inputMode="decimal"
                                            className={cellInput}
                                            value={line.weight_kg}
                                            onChange={(e) =>
                                                setLine(
                                                    index,
                                                    "weight_kg",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </td>
                                    <td className="px-3 py-2">
                                        <input
                                            type="number"
                                            step="1"
                                            min="0"
                                            inputMode="numeric"
                                            className={cellInput}
                                            value={line.quantity}
                                            onChange={(e) =>
                                                setLine(
                                                    index,
                                                    "quantity",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </td>
                                    <td className="px-3 py-2">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            inputMode="decimal"
                                            className={cellInput}
                                            value={line.unit_price}
                                            onChange={(e) =>
                                                setLine(
                                                    index,
                                                    "unit_price",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </td>
                                    <td className="px-3 py-2">
                                        <input
                                            className={cellInput}
                                            value={line.description}
                                            onChange={(e) =>
                                                setLine(
                                                    index,
                                                    "description",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </td>
                                    <td className="px-3 py-2 text-right whitespace-nowrap font-medium text-muted">
                                        {money(lineTotal(line))}
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        <button
                                            type="button"
                                            onClick={() => removeLine(index)}
                                            disabled={data.items.length <= 1}
                                            className="rounded p-1 text-danger hover:bg-danger-soft disabled:opacity-40"
                                            aria-label="Remove line"
                                        >
                                            ✕
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <p className="mt-2 text-xs text-muted">
                    Enter a <strong>weight (kg)</strong> or a{" "}
                    <strong>quantity</strong> for each line, plus a unit price.
                    The line total is calculated for you; the server
                    recalculates it on save.
                </p>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Field label="Discount" name="discount" error={errors.discount}>
                    <Input
                        name="discount"
                        type="number"
                        step="0.01"
                        min="0"
                        inputMode="decimal"
                        value={data.discount}
                        onChange={(e) => setData("discount", e.target.value)}
                    />
                </Field>

                <Field
                    label="Paid amount"
                    name="paid_amount"
                    hint="Optional. Leave blank if nothing has been paid."
                    error={errors.paid_amount}
                >
                    <Input
                        name="paid_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        inputMode="decimal"
                        value={data.paid_amount}
                        onChange={(e) => setData("paid_amount", e.target.value)}
                    />
                </Field>

                <div className="rounded-control border border-border bg-surface-muted p-4">
                    <p className="text-xs font-medium uppercase tracking-wide text-muted">
                        Subtotal
                    </p>
                    <p className="mt-1 text-lg font-bold text-text">
                        {money(subtotal)}
                    </p>
                </div>
            </div>

            <Field label="Note" name="note" error={errors.note}>
                <Textarea
                    name="note"
                    rows={3}
                    value={data.note}
                    onChange={(e) => setData("note", e.target.value)}
                />
            </Field>

            <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    {isEdit ? "Save changes" : "Record sale"}
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
