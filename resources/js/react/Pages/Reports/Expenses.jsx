import React from "react";
import { withLayout, money, date } from "../../Components/Page";
import { ReportShell } from "./ReportShell";
import { Card } from "../../Components/Card";

/** Expense report — expenses by category, with the category breakdown. */
function ExpensesReport({
    rows,
    expenseTotal,
    byCategory,
    categories,
    filters,
}) {
    const summary = [
        { label: "Total expense", value: money(expenseTotal), tone: "danger" },
    ];

    const catOptions = Object.fromEntries(
        Object.entries(categories || {}).map(([k, v]) => [
            k,
            typeof v === "object" ? v.label : v,
        ]),
    );

    const columns = [
        { key: "date", label: "Date", render: (r) => date(r.date) },
        { key: "category", label: "Category" },
        { key: "pond", label: "Pond" },
        {
            key: "amount",
            label: "Amount",
            align: "right",
            render: (r) => (
                <span className="font-medium text-danger">
                    {money(r.amount)}
                </span>
            ),
        },
        { key: "paid_to", label: "Paid To", render: (r) => r.paid_to || "—" },
        {
            key: "reference",
            label: "Reference",
            render: (r) => r.reference || "—",
        },
    ];

    return (
        <ReportShell
            title="Expense Report"
            subtitle="Recorded expenses over a period, grouped by category."
            breadcrumb={[
                { label: "Reports", href: "/fish-farm/reports" },
                { label: "Expense Report" },
            ]}
            filterAction="reports.expenses"
            extraFilters={[
                {
                    name: "category",
                    label: "Category",
                    placeholder: "All categories",
                    options: catOptions,
                },
            ]}
            summary={summary}
            columns={columns}
            rows={{ ...rows, title: "Expenses" }}
            exportReport="reports.export"
            exportParams={{ report: "expenses" }}
            emptyTitle="No expenses in this period"
            emptyMessage="Widen the date range, or record an expense."
        >
            {byCategory && byCategory.length > 0 && (
                <div className="mt-5">
                    <Card title="By category">
                        <div className="table-shell">
                            <table className="w-full text-sm">
                                <thead className="bg-surface-muted text-xs uppercase text-muted">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium">
                                            Category
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {byCategory.map((c, i) => (
                                        <tr key={i}>
                                            <td className="px-4 py-3">
                                                {c.label ?? c.category ?? "—"}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {money(
                                                    c.value ?? c.amount ?? 0,
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            )}
        </ReportShell>
    );
}

export default withLayout(ExpensesReport, "Expense Report");
