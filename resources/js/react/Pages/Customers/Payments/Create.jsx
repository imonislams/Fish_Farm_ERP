import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout, money, usePermission } from "../../../Components/Page";
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

/** Record Customer Payment — money received, reducing the customer's due. */
function CustomerPaymentsCreate({
    options = {},
    dueByCustomer = {},
    defaultDate = "",
    selectedCustomerId = null,
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const { data, setData, post, processing, errors } = useForm({
        customer_id: selectedCustomerId ?? "",
        amount: "",
        method: "cash",
        paid_on: defaultDate,
        reference: "",
        note: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["customers.payments.store"]);
    };

    const hasCustomers = Object.keys(options.customerOptions || {}).length > 0;

    return (
        <>
            <Head title="Record Customer Payment" />
            <PageHeader
                title="Record Customer Payment"
                subtitle="Money received from a customer. It reduces their outstanding due."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    {
                        label: "Customer Payments",
                        href: routes["customers.payments.index"],
                    },
                    { label: "Record Payment" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                {!hasCustomers ? (
                    <Card tone="warning" title="No customers exist yet">
                        <p className="text-sm text-text-soft">
                            A payment must be from a customer, so add one first.
                        </p>
                        {can("customer.create") &&
                            routes["customers.create"] && (
                                <div className="mt-3">
                                    <Button
                                        href={routes["customers.create"]}
                                        variant="primary"
                                        size="sm"
                                    >
                                        New Customer
                                    </Button>
                                </div>
                            )}
                    </Card>
                ) : (
                    <>
                        <div className="surface-card border border-info/30 p-4 text-sm text-text-soft">
                            Current outstanding due per customer:
                            <ul className="mt-2 space-y-0.5 text-xs">
                                {Object.entries(
                                    options.customerOptions || {},
                                ).map(([id, label]) => (
                                    <li key={id}>
                                        {label}:{" "}
                                        <strong>
                                            {money(dueByCustomer?.[id] ?? 0)}
                                        </strong>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <form onSubmit={submit} className="mt-4">
                            <Card
                                title="Payment details"
                                subtitle="Fields marked with * are required."
                            >
                                <div className="space-y-5">
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <Field
                                            label="Customer"
                                            name="customer_id"
                                            required
                                            error={errors.customer_id}
                                        >
                                            <Select
                                                name="customer_id"
                                                value={data.customer_id}
                                                onChange={(e) =>
                                                    setData(
                                                        "customer_id",
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Select a customer…"
                                                options={
                                                    options.customerOptions ||
                                                    {}
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
                                            label="Method"
                                            name="method"
                                            required
                                            error={errors.method}
                                        >
                                            <Select
                                                name="method"
                                                value={data.method}
                                                onChange={(e) =>
                                                    setData(
                                                        "method",
                                                        e.target.value,
                                                    )
                                                }
                                                options={
                                                    options.methodOptions || {}
                                                }
                                            />
                                        </Field>

                                        <Field
                                            label="Date"
                                            name="paid_on"
                                            required
                                            error={errors.paid_on}
                                        >
                                            <DatePicker
                                                name="paid_on"
                                                value={data.paid_on}
                                                onChange={(e) =>
                                                    setData(
                                                        "paid_on",
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                    </div>

                                    <Field
                                        label="Reference"
                                        name="reference"
                                        hint="Optional. A receipt or transaction number."
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
                                    Record payment
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

export default withLayout(CustomerPaymentsCreate, "Record Customer Payment");
