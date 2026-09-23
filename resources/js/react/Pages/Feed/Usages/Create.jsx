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

/** Record Food Usage (feed stock OUT, into a pond). */
function UsagesCreate({ options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const { data, setData, post, processing, errors } = useForm({
        pond_id: "",
        feed_type_id: "",
        quantity_kg: "",
        used_on: defaultDate,
        note: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["feed.usages.store"]);
    };

    const hasTypes = Object.keys(options.typeOptions || {}).length > 0;
    const hasPonds = Object.keys(options.pondOptions || {}).length > 0;

    return (
        <>
            <Head title="Record Food Usage" />
            <PageHeader
                title="Record Food Usage"
                subtitle="Feed given to a pond. The feed type's stock is reduced by the quantity entered."
                breadcrumb={[
                    { label: "Food Management" },
                    { label: "Food Usage", href: routes["feed.usages.index"] },
                    { label: "Record Usage" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                {!hasTypes ? (
                    <Card tone="warning" title="No feed types exist yet">
                        <p className="text-sm text-text-soft">
                            A usage must name a feed type, so create one first.
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
                ) : !hasPonds ? (
                    <Card tone="warning" title="No ponds exist yet">
                        <p className="text-sm text-text-soft">
                            Create a pond before recording feed usage.
                        </p>
                        {can("pond.create") && routes["ponds.create"] && (
                            <div className="mt-3">
                                <Button
                                    href={routes["ponds.create"]}
                                    variant="primary"
                                    size="sm"
                                >
                                    New Pond
                                </Button>
                            </div>
                        )}
                    </Card>
                ) : (
                    <>
                        <div className="surface-card border border-info/30 p-4 text-sm text-text-soft">
                            Usage can never take a feed type's stock below zero.
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
                                title="Usage details"
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
                                                    setData(
                                                        "pond_id",
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Select a pond…"
                                                options={
                                                    options.pondOptions || {}
                                                }
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
                                                options={
                                                    options.typeOptions || {}
                                                }
                                            />
                                        </Field>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <Field
                                            label="Quantity (kg)"
                                            name="quantity_kg"
                                            required
                                            hint="Cannot exceed available stock."
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
                                            name="used_on"
                                            required
                                            error={errors.used_on}
                                        >
                                            <DatePicker
                                                name="used_on"
                                                value={data.used_on}
                                                onChange={(e) =>
                                                    setData(
                                                        "used_on",
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
                                    Record usage
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

export default withLayout(UsagesCreate, "Record Food Usage");
