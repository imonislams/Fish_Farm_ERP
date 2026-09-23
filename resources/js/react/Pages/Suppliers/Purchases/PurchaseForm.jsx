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
import { money } from "../../../Components/Page";

/**
 * PurchaseForm — purchase header + dynamic line items.
 *
 * A `feed` line must name a feed type; a `fingerlings` line must name a species.
 * The relevant picker is shown per line. Line totals and the subtotal are live for
 * convenience; the SERVER recalculates and validates everything.
 */
export default function PurchaseForm({
    purchase = null,
    options = {},
    action,
    method = "post",
    defaultDate = "",
}) {
    const isEdit = method === "put";
    const itemTypes = options.itemTypeOptions || {};
    const defaultType = Object.keys(itemTypes)[0] || "other";

    const emptyLine = {
        item_type: defaultType,
        feed_type_id: "",
        fish_species_id: "",
        description: "",
        quantity: "",
        unit_cost: "",
    };

    const seedLines =
        purchase?.items?.length > 0
            ? purchase.items.map((i) => ({
                  item_type: i.item_type ?? defaultType,
                  feed_type_id: i.feed_type_id ?? "",
                  fish_species_id: i.fish_species_id ?? "",
                  description: i.description ?? "",
                  quantity: i.quantity ?? "",
                  unit_cost: i.unit_cost ?? "",
              }))
            : [{ ...emptyLine }];

    const { data, setData, post, put, processing, errors } = useForm({
        supplier_id: purchase?.supplier_id ?? "",
        invoice_no: purchase?.invoice_no ?? "",
        purchase_date: purchase?.purchase_date ?? defaultDate,
        discount: purchase?.discount ?? 0,
        paid_amount: purchase?.paid_amount ?? 0,
        note: purchase?.note ?? "",
        items: seedLines,
    });

    const lineTotal = (line) =>
        (parseFloat(line.quantity || 0) || 0) *
        (parseFloat(line.unit_cost || 0) || 0);
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
                    label="Supplier"
                    name="supplier_id"
                    required
                    error={errors.supplier_id}
                >
                    <Select
                        name="supplier_id"
                        value={data.supplier_id}
                        onChange={(e) => setData("supplier_id", e.target.value)}
                        placeholder="Select a supplier…"
                        options={options.supplierOptions || {}}
                    />
                </Field>

                <Field
                    label="Invoice number"
                    name="invoice_no"
                    hint="Optional. The supplier's own reference."
                    error={errors.invoice_no}
                >
                    <Input
                        name="invoice_no"
                        value={data.invoice_no}
                        onChange={(e) => setData("invoice_no", e.target.value)}
                    />
                </Field>

                <Field
                    label="Purchase date"
                    name="purchase_date"
                    required
                    error={errors.purchase_date}
                >
                    <DatePicker
                        name="purchase_date"
                        value={data.purchase_date}
                        onChange={(e) =>
                            setData("purchase_date", e.target.value)
                        }
                    />
                </Field>
            </div>

            <div>
                <div className="mb-2 flex items-center justify-between gap-3">
                    <h3 className="text-sm font-semibold text-text">
                        Purchase items
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
                                    "Type",
                                    "Feed type",
                                    "Species",
                                    "Description",
                                    "Qty",
                                    "Unit cost",
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
                                            value={line.item_type}
                                            onChange={(e) =>
                                                setLine(
                                                    index,
                                                    "item_type",
                                                    e.target.value,
                                                )
                                            }
                                        >
                                            {Object.entries(itemTypes).map(
                                                ([v, l]) => (
                                                    <option key={v} value={v}>
                                                        {l}
                                                    </option>
                                                ),
                                            )}
                                        </select>
                                    </td>
                                    <td className="px-3 py-2">
                                        {line.item_type === "feed" ? (
                                            <select
                                                className={cellInput}
                                                value={line.feed_type_id}
                                                onChange={(e) =>
                                                    setLine(
                                                        index,
                                                        "feed_type_id",
                                                        e.target.value,
                                                    )
                                                }
                                            >
                                                <option value="">—</option>
                                                {Object.entries(
                                                    options.feedTypeOptions ||
                                                        {},
                                                ).map(([v, l]) => (
                                                    <option key={v} value={v}>
                                                        {l}
                                                    </option>
                                                ))}
                                            </select>
                                        ) : (
                                            <span className="text-muted">
                                                —
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        {line.item_type === "fingerlings" ? (
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
                                                    options.speciesOptions ||
                                                        {},
                                                ).map(([v, l]) => (
                                                    <option key={v} value={v}>
                                                        {l}
                                                    </option>
                                                ))}
                                            </select>
                                        ) : (
                                            <span className="text-muted">
                                                —
                                            </span>
                                        )}
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
                                    <td className="px-3 py-2">
                                        <input
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            inputMode="decimal"
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
                                            value={line.unit_cost}
                                            onChange={(e) =>
                                                setLine(
                                                    index,
                                                    "unit_cost",
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
                    A <strong>feed</strong> line must name a feed type; a{" "}
                    <strong>fish / fingerlings</strong> line must name a
                    species. The line total is calculated for you; the server
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

                <div className="rounded-control border-border bg-surface-muted p-4">
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
                    {isEdit ? "Save changes" : "Record purchase"}
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
