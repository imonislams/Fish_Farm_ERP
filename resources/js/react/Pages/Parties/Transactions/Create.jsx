import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { withLayout, money, usePermission } from "../../../Components/Page";
import { PageHeader } from "../../../Components/PageHeader";
import { Card } from "../../../Components/Card";
import Button from "../../../Components/Button";
import { Field, Input, Select, DatePicker } from "../../../Components/Form";

/** Record Party Transaction — a debit increases what they owe; a credit reduces it. */
function PartyTransactionsCreate({
    options = {},
    balanceByParty = {},
    defaultDate = "",
    selectedPartyId = null,
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();

    const { data, setData, post, processing, errors } = useForm({
        party_id: selectedPartyId ?? "",
        entry_type: "debit",
        amount: "",
        entry_date: defaultDate,
        reference: "",
        description: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(routes["parties.transactions.store"]);
    };

    const hasParties = Object.keys(options.partyOptions || {}).length > 0;

    return (
        <>
            <Head title="Record Party Transaction" />
            <PageHeader
                title="Record Party Transaction"
                subtitle="A debit increases what the party owes the farm; a credit reduces it."
                breadcrumb={[
                    { label: "Sales & Accounts" },
                    {
                        label: "Party Transactions",
                        href: routes["parties.transactions"],
                    },
                    { label: "New Entry" },
                ]}
            />

            <div className="mt-5 max-w-2xl">
                {!hasParties ? (
                    <Card tone="warning" title="No parties exist yet">
                        <p className="text-sm text-text-soft">
                            A transaction must be against a party, so add one
                            first.
                        </p>
                        {can("party.create") && routes["parties.create"] && (
                            <div className="mt-3">
                                <Button
                                    href={routes["parties.create"]}
                                    variant="primary"
                                    size="sm"
                                >
                                    New Party
                                </Button>
                            </div>
                        )}
                    </Card>
                ) : (
                    <>
                        <div className="surface-card border-info/30 p-4 text-sm text-text-soft">
                            Current balance per party (positive = they owe the
                            farm):
                            <ul className="mt-2 space-y-0.5 text-xs">
                                {Object.entries(options.partyOptions || {}).map(
                                    ([id, label]) => (
                                        <li key={id}>
                                            {label}:{" "}
                                            <strong>
                                                {money(
                                                    balanceByParty?.[id] ?? 0,
                                                )}
                                            </strong>
                                        </li>
                                    ),
                                )}
                            </ul>
                        </div>

                        <form onSubmit={submit} className="mt-4">
                            <Card
                                title="Transaction details"
                                subtitle="Fields marked with * are required."
                            >
                                <div className="space-y-5">
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <Field
                                            label="Party"
                                            name="party_id"
                                            required
                                            error={errors.party_id}
                                        >
                                            <Select
                                                name="party_id"
                                                value={data.party_id}
                                                onChange={(e) =>
                                                    setData(
                                                        "party_id",
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Select a party…"
                                                options={
                                                    options.partyOptions || {}
                                                }
                                            />
                                        </Field>

                                        <Field
                                            label="Entry type"
                                            name="entry_type"
                                            required
                                            error={errors.entry_type}
                                        >
                                            <Select
                                                name="entry_type"
                                                value={data.entry_type}
                                                onChange={(e) =>
                                                    setData(
                                                        "entry_type",
                                                        e.target.value,
                                                    )
                                                }
                                                options={{
                                                    debit: "Debit — they owe the farm more",
                                                    credit: "Credit — they settled / the farm owes them",
                                                }}
                                            />
                                        </Field>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                                                setData(
                                                    "reference",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Description"
                                        name="description"
                                        error={errors.description}
                                    >
                                        <Input
                                            name="description"
                                            value={data.description}
                                            onChange={(e) =>
                                                setData(
                                                    "description",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>
                            </Card>

                            <div className="mt-4 flex-wrap items-center gap-2">
                                <Button
                                    type="submit"
                                    variant="primary"
                                    loading={processing}
                                >
                                    Record transaction
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

export default withLayout(PartyTransactionsCreate, "Record Party Transaction");
