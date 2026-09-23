import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, money } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, KpiCard } from "../../Components/Card";
import Button from "../../Components/Button";
import { Field, DatePicker } from "../../Components/Form";

/** Finance Profit & Loss — income minus expense for a period. */
function ProfitLoss({ figures = {}, filters = {}, generatedAt = "" }) {
    const { props } = usePage();
    const routes = props.routes || {};

    const [from, setFrom] = React.useState(filters.from || "");
    const [to, setTo] = React.useState(filters.to || "");
    const [busy, setBusy] = React.useState(false);

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["finance.profit-loss"] || window.location.pathname,
            { from, to },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const exportParams = new URLSearchParams(
        Object.entries({ from, to }).filter(([, v]) => v !== "" && v != null),
    ).toString();
    const exportHref = routes["finance.profit-loss.export"]
        ? exportParams
            ? `${routes["finance.profit-loss.export"]}?${exportParams}`
            : routes["finance.profit-loss.export"]
        : "";

    const {
        sales = 0,
        miscIncome = 0,
        income = 0,
        expense = 0,
        profit = 0,
        receivable = 0,
        payable = 0,
    } = figures;

    return (
        <>
            <Head title="Profit & Loss" />

            <PageHeader
                title="Profit & Loss"
                subtitle="Income minus expense for a period. A negative result is a real loss, shown as one."
                breadcrumb={[{ label: "Finance" }, { label: "Profit & Loss" }]}
                actions={
                    <>
                        {routes["finance.income.index"] && (
                            <Button
                                href={routes["finance.income.index"]}
                                variant="outline"
                                icon="plus"
                            >
                                Income
                            </Button>
                        )}
                        {routes["finance.expenses.index"] && (
                            <Button
                                href={routes["finance.expenses.index"]}
                                variant="outline"
                                icon="alert"
                            >
                                Expenses
                            </Button>
                        )}
                        {exportHref && (
                            <a
                                href={exportHref}
                                className="inline-flex items-center justify-center gap-2 rounded-control border border-border-strong bg-surface px-3.5 py-2 text-sm font-medium text-text transition-colors hover:bg-surface-muted"
                            >
                                Export P&amp;L CSV
                            </a>
                        )}
                    </>
                }
            />

            <div className="mt-5">
                <Card title="Period">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <Field label="From" name="from">
                            <DatePicker
                                name="from"
                                value={from}
                                onChange={(e) => setFrom(e.target.value)}
                            />
                        </Field>
                        <Field label="To" name="to">
                            <DatePicker
                                name="to"
                                value={to}
                                onChange={(e) => setTo(e.target.value)}
                            />
                        </Field>
                        <div className="flex items-end gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={busy}
                            >
                                Apply
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    label="Total income"
                    value={money(income)}
                    icon="plus"
                    tone="success"
                    hint="Sales plus other income"
                />
                <KpiCard
                    label="Total expense"
                    value={money(expense)}
                    icon="alert"
                    tone="danger"
                    hint="Recorded expenses"
                />
                <KpiCard
                    label="Net profit"
                    value={money(profit)}
                    icon="chart"
                    tone={profit < 0 ? "danger" : "success"}
                    hint={
                        profit < 0 ? "Loss for this period" : "Income − expense"
                    }
                />
                <KpiCard
                    label="Receivable / payable"
                    value={`${money(receivable)} / ${money(payable)}`}
                    icon="report"
                    tone="warning"
                    hint="Customer due / supplier due (all time)"
                />
            </div>

            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Card padded={false} title="Income">
                    <div className="table-shell">
                        <table className="w-full text-sm">
                            <thead className="bg-surface-muted text-xs uppercase text-muted">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Line
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                <tr>
                                    <td className="px-4 py-3 text-text-soft">
                                        Fish sales
                                    </td>
                                    <td className="px-4 py-3 text-right whitespace-nowrap font-medium text-text">
                                        {money(sales)}
                                    </td>
                                </tr>
                                <tr>
                                    <td className="px-4 py-3 text-text-soft">
                                        Other income
                                    </td>
                                    <td className="px-4 py-3 text-right whitespace-nowrap font-medium text-text">
                                        {money(miscIncome)}
                                    </td>
                                </tr>
                                <tr className="border-t border-border">
                                    <td className="px-4 py-3 font-semibold text-text">
                                        Total income
                                    </td>
                                    <td className="px-4 py-3 text-right whitespace-nowrap font-bold text-success">
                                        {money(income)}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card padded={false} title="Result">
                    <div className="table-shell">
                        <table className="w-full text-sm">
                            <thead className="bg-surface-muted text-xs uppercase text-muted">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Line
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                <tr>
                                    <td className="px-4 py-3 text-text-soft">
                                        Total income
                                    </td>
                                    <td className="px-4 py-3 text-right whitespace-nowrap font-medium text-text">
                                        {money(income)}
                                    </td>
                                </tr>
                                <tr>
                                    <td className="px-4 py-3 text-text-soft">
                                        Total expense
                                    </td>
                                    <td className="px-4 py-3 text-right whitespace-nowrap font-medium text-text">
                                        {money(expense)}
                                    </td>
                                </tr>
                                <tr className="border-t border-border">
                                    <td
                                        className={`px-4 py-3 font-semibold ${profit < 0 ? "text-danger" : "text-text"}`}
                                    >
                                        {profit < 0 ? "Net loss" : "Net profit"}
                                    </td>
                                    <td
                                        className={`px-4 py-3 text-right whitespace-nowrap font-bold ${profit < 0 ? "text-danger" : "text-success"}`}
                                    >
                                        {money(profit)}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <div className="mt-5 surface-card border border-info/30 p-4 text-sm text-text-soft">
                <strong className="text-text">
                    Net profit = income − expense.
                </strong>{" "}
                A negative figure is a real loss for this period and is
                displayed as one, never clamped to zero.
                {generatedAt && ` Generated ${generatedAt}.`}
            </div>
        </>
    );
}

export default withLayout(ProfitLoss, "Profit & Loss");
