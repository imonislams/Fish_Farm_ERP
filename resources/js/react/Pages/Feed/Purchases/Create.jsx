import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../../Components/Page";
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

/** Record Food Purchase (feed stock IN). */
function PurchasesCreate({ options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const { data, setData, post, processing, errors } = useForm({
        feed_type_id: "",
        quantity_kg: "",
        unit_cost: "",
        paid_amount: "",
        purchased_on: defaultDate,
        supplier_name: "",
        invoice_no: "",
        note: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["feed.purchases.store"]);
    };

    const hasTypes = Object.keys(options.typeOptions || {}).length > 0;

    return (
        <>
            <Head title="Record Food Purchase" />
            <PageHeader
                title="Record Food Purchase"
                subtitle="Record feed bought into stock. The feed type's stock is increased by the quantity entered."
                breadcrumb={[
                    { label: "Food Management" },
                    {
                        label: "Food Purchase",
                        href: routes["feed.purchases.index"],
                    },
                    { label: "Record Purchase" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                {!hasTypes ? (
                    <Card tone="warning" title="No feed types exist yet">
                        <p className="text-sm text-text-soft">
                            A purchase must name a feed type, so create one
                            first.
                        </p>
                        {can("feed.type.manage") &&
                            routes["feed.types.create"] && (
                                <div className="mt-3">
                                    <Button
                                        href={routes["feed.types.create"]}
                                        variant="primary"
                                        size="sm"
                                    >
                                        New Food Type
                                    </Button>
                                </div>
                            )}
                    </Card>
                ) : (
                    <form onSubmit={submit}>
                        <Card
                            title="Purchase details"
                            subtitle="Fields marked with * are required."
                        >
                            <div className="space-y-5">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

                                    <Field
                                        label="Quantity (kg)"
                                        name="quantity_kg"
                                        required
                                        hint="Total weight in kilograms."
                                        error={errors.quantity_kg}
                                    >
                                        <Input
                                            name="quantity_kg"
                                            type="number"
                                            step="0.001"
                                            min="0.001"
                                            inputMode="decimal"
                                            value={data.quantity_kg}
                                            onChange={(e) =>
                                                setData(
                                                    "quantity_kg",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <Field
                                        label="Unit cost"
                                        name="unit_cost"
                                        hint="Optional. Cost per kg."
                                        error={errors.unit_cost}
                                    >
                                        <Input
                                            name="unit_cost"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            inputMode="decimal"
                                            value={data.unit_cost}
                                            onChange={(e) =>
                                                setData(
                                                    "unit_cost",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Paid amount"
                                        name="paid_amount"
                                        hint="Optional. Amount paid now."
                                        error={errors.paid_amount}
                                    >
                                        <Input
                                            name="paid_amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            inputMode="decimal"
                                            value={data.paid_amount}
                                            onChange={(e) =>
                                                setData(
                                                    "paid_amount",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Purchase date"
                                        name="purchased_on"
                                        required
                                        error={errors.purchased_on}
                                    >
                                        <DatePicker
                                            name="purchased_on"
                                            value={data.purchased_on}
                                            onChange={(e) =>
                                                setData(
                                                    "purchased_on",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field
                                        label="Supplier"
                                        name="supplier_name"
                                        hint="Optional. Where the feed came from."
                                        error={errors.supplier_name}
                                    >
                                        <Input
                                            name="supplier_name"
                                            value={data.supplier_name}
                                            onChange={(e) =>
                                                setData(
                                                    "supplier_name",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Invoice number"
                                        name="invoice_no"
                                        hint="Optional. The supplier's invoice reference."
                                        error={errors.invoice_no}
                                    >
                                        <Input
                                            name="invoice_no"
                                            value={data.invoice_no}
                                            onChange={(e) =>
                                                setData(
                                                    "invoice_no",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>

                                <Field
                                    label="Note"
                                    name="note"
                                    error={errors.note}
                                >
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
                                Record purchase
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
                )}
            </div>
        </>
    );
}

export default withLayout(PurchasesCreate, "Record Food Purchase");
