import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout } from "../../../Components/Page";
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

/** Record Income — income that is not a fish sale. */
function IncomeCreate({ options = {}, defaultDate = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};

    const { data, setData, post, processing, errors } = useForm({
        category: "",
        amount: "",
        entry_date: defaultDate,
        pond_id: "",
        reference: "",
        note: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["finance.income.store"]);
    };

    return (
        <>
            <Head title="Record Income" />
            <PageHeader
                title="Record Income"
                subtitle="Income that is not a fish sale. Choosing a pond also credits that pond's ledger."
                breadcrumb={[
                    { label: "Finance" },
                    {
                        label: "Income",
                        href: routes["finance.income.index"],
                    },
                    { label: "Record Income" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                <form onSubmit={submit}>
                    <Card
                        title="Income details"
                        subtitle="Fields marked with * are required."
                    >
                        <div className="space-y-5">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Field
                                    label="Category"
                                    name="category"
                                    required
                                    error={errors.category}
                                >
                                    <Select
                                        name="category"
                                        value={data.category}
                                        onChange={(e) =>
                                            setData("category", e.target.value)
                                        }
                                        placeholder="Select a category…"
                                        options={options.categoryOptions || {}}
                                    />
                                </Field>

                                <Field
                                    label="Amount"
                                    name="amount"
                                    required
                                    error={errors.amount}
                                >
                                    <Input
                                        name="amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        inputMode="decimal"
                                        value={data.amount}
                                        onChange={(e) =>
                                            setData("amount", e.target.value)
                                        }
                                    />
                                </Field>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Field
                                    label="Date"
                                    name="entry_date"
                                    required
                                    error={errors.entry_date}
                                >
                                    <DatePicker
                                        name="entry_date"
                                        value={data.entry_date}
                                        onChange={(e) =>
                                            setData(
                                                "entry_date",
                                                e.target.value,
                                            )
                                        }
                                    />
                                </Field>

                                <Field
                                    label="Pond"
                                    name="pond_id"
                                    hint="Optional. Attribute this money to a specific pond."
                                    error={errors.pond_id}
                                >
                                    <Select
                                        name="pond_id"
                                        value={data.pond_id}
                                        onChange={(e) =>
                                            setData("pond_id", e.target.value)
                                        }
                                        placeholder="Farm-wide (no pond)"
                                        options={options.pondOptions || {}}
                                    />
                                </Field>
                            </div>

                            <Field
                                label="Reference"
                                name="reference"
                                hint="Optional. A receipt or document number."
                                error={errors.reference}
                            >
                                <Input
                                    name="reference"
                                    value={data.reference}
                                    onChange={(e) =>
                                        setData("reference", e.target.value)
                                    }
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
                            Record income
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
            </div>
        </>
    );
}

export default withLayout(IncomeCreate, "Record Income");
