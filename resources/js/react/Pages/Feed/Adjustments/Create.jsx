import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout, usePermission, num } from "../../../Components/Page";
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

/** Adjust Food Stock — a manual correction with a required reason. */
function AdjustmentsCreate({ options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const { data, setData, post, processing, errors } = useForm({
        feed_type_id: "",
        direction: "",
        quantity_kg: "",
        adjusted_on: defaultDate,
        reason: "",
        note: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["feed.adjustments.store"]);
    };

    const hasTypes = Object.keys(options.typeOptions || {}).length > 0;

    return (
        <>
            <Head title="Adjust Food Stock" />
            <PageHeader
                title="Adjust Food Stock"
                subtitle="Correct a feed type's stock by hand. A reason is required for every adjustment."
                breadcrumb={[
                    { label: "Food Management" },
                    {
                        label: "Stock Adjustment",
                        href: routes["feed.adjustments.index"],
                    },
                    { label: "Adjust Stock" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                {!hasTypes ? (
                    <Card tone="warning" title="No feed types exist yet">
                        <p className="text-sm text-text-soft">
                            Create a feed type before adjusting stock.
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
                    <>
                        <div className="surface-card border border-info/30 p-4 text-sm text-text-soft">
                            An <em>increase</em> needs no limit; a{" "}
                            <em>decrease</em> cannot take stock below zero.
                            Current stock:
                            <ul className="mt-2 space-y-0.5 text-xs">
                                {Object.entries(options.typeOptions || {}).map(
                                    ([id, label]) => (
                                        <li key={id}>
                                            {label}:{" "}
                                            <strong>
                                                {num(
                                                    options.stockByType?.[id] ??
                                                        0,
                                                    3,
                                                )}
                                            </strong>{" "}
                                            kg
                                        </li>
                                    ),
                                )}
                            </ul>
                        </div>

                        <form onSubmit={submit} className="mt-4">
                            <Card
                                title="Adjustment details"
                                subtitle="Fields marked with * are required."
                            >
                                <div className="space-y-5">
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

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <Field
                                            label="Direction"
                                            name="direction"
                                            required
                                            hint="Does stock go up or down?"
                                            error={errors.direction}
                                        >
                                            <Select
                                                name="direction"
                                                value={data.direction}
                                                onChange={(e) =>
                                                    setData(
                                                        "direction",
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Select…"
                                                options={
                                                    options.directionOptions ||
                                                    {}
                                                }
                                            />
                                        </Field>

                                        <Field
                                            label="Quantity (kg)"
                                            name="quantity_kg"
                                            required
                                            hint="A positive amount."
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

                                        <Field
                                            label="Date"
                                            name="adjusted_on"
                                            required
                                            error={errors.adjusted_on}
                                        >
                                            <DatePicker
                                                name="adjusted_on"
                                                value={data.adjusted_on}
                                                onChange={(e) =>
                                                    setData(
                                                        "adjusted_on",
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                    </div>

                                    <Field
                                        label="Reason"
                                        name="reason"
                                        required
                                        hint="Stock is never changed without a reason."
                                        error={errors.reason}
                                    >
                                        <Select
                                            name="reason"
                                            value={data.reason}
                                            onChange={(e) =>
                                                setData(
                                                    "reason",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Select a reason…"
                                            options={
                                                options.reasonOptions || {}
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Note"
                                        name="note"
                                        hint="Optional extra detail about this correction."
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
                                    Record adjustment
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
                    </>
                )}
            </div>
        </>
    );
}

export default withLayout(AdjustmentsCreate, "Adjust Food Stock");
