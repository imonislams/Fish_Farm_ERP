import React from "react";
import { withLayout, money } from "../../Components/Page";
import { ReportShell } from "./ReportShell";

/** Pond report — pond ledger profitability (income − expense per pond). */
function PondsReport({ rows = [], totals }) {
    const summary = [
        {
            label: "Total income",
            value: money(totals?.income ?? 0),
            tone: "success",
        },
        {
            label: "Total expense",
            value: money(totals?.expense ?? 0),
            tone: "danger",
        },
        {
            label: "Total profit",
            value: money(totals?.profit ?? 0),
            tone: (totals?.profit ?? 0) >= 0 ? "success" : "danger",
        },
    ];

    const columns = [
        { key: "pond_number", label: "Pond No" },
        { key: "name", label: "Pond" },
        {
            key: "income",
            label: "Income",
            align: "right",
            render: (r) => (
                <span className="text-success">{money(r.income)}</span>
            ),
        },
        {
            key: "expense",
            label: "Expense",
            align: "right",
            render: (r) => (
                <span className="text-danger">{money(r.expense)}</span>
            ),
        },
        {
            key: "profit",
            label: "Profit",
            align: "right",
            render: (r) => (
                <span
                    className={`font-medium ${r.profit >= 0 ? "text-text" : "text-danger"}`}
                >
                    {money(r.profit)}
                </span>
            ),
        },
    ];

    const paginated = {
        data: rows,
        current_page: 1,
        last_page: 1,
        total: rows.length,
        from: rows.length ? 1 : 0,
        to: rows.length,
        links: [],
    };

    return (
        <ReportShell
            title="Pond Report"
            subtitle="Pond ledger profitability — income and expense per pond."
            breadcrumb={[
                { label: "Reports", href: "/fish-farm/reports" },
                { label: "Pond Report" },
            ]}
            filterAction="reports.ponds"
            summary={summary}
            columns={columns}
            rows={paginated}
            exportReport="reports.export"
            exportParams={{ report: "ponds" }}
            emptyTitle="No ponds found"
            emptyMessage="Add a pond to see its ledger summary."
        />
    );
}

export default withLayout(PondsReport, "Pond Report");
