import React from "react";
import { withLayout, money } from "../../Components/Page";
import { ReportShell } from "./ReportShell";
import { Card, KpiCard } from "../../Components/Card";

/** Profit & Loss — income − expense = net profit (a real loss shows as negative). */
function ProfitLoss({
    sales,
    miscIncome,
    income,
    expense,
    profit,
    receivable,
    payable,
}) {
    const summary = [
        { label: "Income", value: money(income), tone: "success" },
        { label: "Expense", value: money(expense), tone: "danger" },
        {
            label: "Net profit",
            value: money(profit),
            tone: profit >= 0 ? "success" : "danger",
        },
        { label: "Receivable", value: money(receivable), tone: "primary" },
        { label: "Payable", value: money(payable), tone: "warning" },
    ];

    const lines = [
        ["Fish sales", sales],
        ["Other income", miscIncome],
        ["Total income", income],
        ["Total expense", expense],
        ["Net profit", profit],
    ];

    return (
        <ReportShell
            title="Profit & Loss"
            subtitle="Income − expense = net profit. Every figure is a real total."
            breadcrumb={[
                { label: "Reports", href: "/fish-farm/reports" },
                { label: "Profit & Loss" },
            ]}
            filterAction="reports.profit-loss"
            summary={summary}
            exportReport="reports.export"
            exportParams={{ report: "profit-loss" }}
        >
            <div className="mt-5">
                <Card title="Statement" padded={false}>
                    <div className="table-shell">
                        <table className="w-full text-sm">
                            <tbody className="divide-y divide-border">
                                {lines.map(([label, value], i) => {
                                    const bold = i >= 2;
                                    return (
                                        <tr
                                            key={label}
                                            className={
                                                i === lines.length - 1
                                                    ? "bg-surface-muted"
                                                    : ""
                                            }
                                        >
                                            <td
                                                className={`px-4 py-3 ${bold ? "font-semibold text-text" : "text-text-soft"}`}
                                            >
                                                {label}
                                            </td>
                                            <td
                                                className={`px-4 py-3 text-right ${bold ? "font-semibold" : ""} ${label === "Net profit" && value < 0 ? "text-danger" : "text-text"}`}
                                            >
                                                {money(value)}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </ReportShell>
    );
}

export default withLayout(ProfitLoss, "Profit & Loss");
