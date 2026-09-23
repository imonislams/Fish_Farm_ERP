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

/** Record Expense — a farm cost. */
function ExpensesCreate({
    options = {},
    hasCategories = false,
    defaultDate = "",
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const { data, setData, post, processing, errors } = useForm({
        expense_category_id: "",
        amount: "",
        entry_date: defaultDate,
        pond_id: "",
        paid_to: "",
        reference: "",
        note: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["finance.expenses.store"]);
    };

    return (
        <>
            <Head title="Record Expense" />
            <PageHeader
                title="Record Expense"
                subtitle="A farm cost. Choosing a pond also debits that pond's ledger."
                breadcrumb={[
                    { label: "Finance" },
                    {
                        label: "Expenses",
                        href: routes["finance.expenses.index"],
                    },
                    { label: "Record Expense" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                {!hasCategories ? (
                    <Card
                        tone="warning"
                        title="No expense categories exist yet"
                    >
                        <p className="text-sm text-text-soft">
                            An expense must be classified, so add a category
                            first.
                        </p>
                        {can("expense.category.manage") &&
                            routes["finance.categories.create"] && (
                                <div className="mt-3">
                                    <Button
                                        href={
                                            routes["finance.categories.create"]
                                        }
                                        variant="primary"
                                        size="sm"
                                    >
                                        New Category
                                    </Button>
                                </div>
                            )}
                    </Card>
                ) : (
                    <form onSubmit={submit}>
                        <Card
                            title="Expense details"
                            subtitle="Fields marked with * are required."
                        >
                            <div className="space-y-5">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field
                                        label="Category"
                                        name="expense_category_id"
                                        required
                                        error={errors.expense_category_id}
                                    >
                                        <Select
                                            name="expense_category_id"
                                            value={data.expense_category_id}
                                            onChange={(e) =>
                                                setData(
                                                    "expense_category_id",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Select a category…"
                                            options={
                                                options.categoryOptions || {}
                                            }
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
                                                setData(
                                                    "amount",
                                                    e.target.value,
                                                )
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
                                        hint="Optional. Attribute this cost to a specific pond."
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
                                            placeholder="Farm-wide (no pond)"
                                            options={options.pondOptions || {}}
                                        />
                                    </Field>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field
                                        label="Paid to"
                                        name="paid_to"
                                        hint="Optional. Who received the money."
                                        error={errors.paid_to}
                                    >
                                        <Input
                                            name="paid_to"
                                            value={data.paid_to}
                                            onChange={(e) =>
                                                setData(
                                                    "paid_to",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Reference"
                                        name="reference"
                                        hint="Optional. A voucher or receipt number."
                                        error={errors.reference}
                                    >
                                        <Input
                                            name="reference"
                                            value={data.reference}
                                            onChange={(e) =>
                                                setData(
                                                    "reference",
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
                                Record expense
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

export default withLayout(ExpensesCreate, "Record Expense");
