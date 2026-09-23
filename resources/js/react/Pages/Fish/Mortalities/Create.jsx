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

/** Record Mortality — fish deaths in a pond (stock OUT). */
function MortalitiesCreate({ options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const { data, setData, post, processing, errors } = useForm({
        pond_id: "",
        quantity: "",
        avg_weight_g: "",
        recorded_on: defaultDate,
        cause: "",
        note: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["fish.mortalities.store"]);
    };

    const hasPonds = Object.keys(options.pondOptions || {}).length > 0;

    return (
        <>
            <Head title="Record Mortality" />
            <PageHeader
                title="Record Mortality"
                subtitle="Record fish deaths. The pond's live stock is reduced by the quantity entered."
                breadcrumb={[
                    { label: "Fish Stock" },
                    {
                        label: "Mortality",
                        href: routes["fish.mortalities.index"],
                    },
                    { label: "Record Mortality" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                {!hasPonds ? (
                    <Card tone="warning" title="No ponds exist yet">
                        <p className="text-sm text-text-soft">
                            Create a pond before recording a mortality.
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
                            A mortality can never take a pond's stock below zero.
                            Live stock per pond:
                            <ul className="mt-2 space-y-0.5 text-xs">
                                {Object.entries(options.pondOptions || {}).map(
                                    ([id, label]) => (
                                        <li key={id}>
                                            {label}:{" "}
                                            <strong>
                                                {Number(
                                                    options.stockByPond?.[id] ?? 0,
                                                ).toLocaleString()}
                                            </strong>{" "}
                                            fish
                                        </li>
                                    ),
                                )}
                            </ul>
                        </div>

                        <form onSubmit={submit} className="mt-4">
                            <Card
                                title="Mortality details"
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
                                                options={options.pondOptions || {}}
                                            />
                                        </Field>

                                        <Field
                                            label="Quantity"
                                            name="quantity"
                                            required
                                            hint="Whole number of fish. Cannot exceed live stock."
                                            error={errors.quantity}
                                        >
                                            <Input
                                                name="quantity"
                                                type="number"
                                                step="1"
                                                min="1"
                                                inputMode="numeric"
                                                value={data.quantity}
                                                onChange={(e) =>
                                                    setData(
                                                        "quantity",
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <Field
                                            label="Date"
                                            name="recorded_on"
                                            required
                                            error={errors.recorded_on}
                                        >
                                            <DatePicker
                                                name="recorded_on"
                                                value={data.recorded_on}
                                                onChange={(e) =>
                                                    setData(
                                                        "recorded_on",
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </Field>

                                        <Field
                                            label="Average weight (g)"
                                            name="avg_weight_g"
                                            hint="Optional."
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
                                                    setData(
                                                        "avg_weight_g",
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                    </div>

                                    <Field
                                        label="Cause"
                                        name="cause"
                                        hint="Optional. Leave blank if the cause is not known."
                                        error={errors.cause}
                                    >
                                        <Select
                                            name="cause"
                                            value={data.cause}
                                            onChange={(e) =>
                                                setData("cause", e.target.value)
                                            }
                                            placeholder="Not recorded"
                                            options={options.causeOptions || {}}
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
                                    Record mortality
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

export default withLayout(MortalitiesCreate, "Record Mortality");