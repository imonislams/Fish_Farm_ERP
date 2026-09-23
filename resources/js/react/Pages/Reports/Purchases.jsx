import React from "react";
import { withLayout, money, date } from "../../Components/Page";
import { ReportShell } from "./ReportShell";
import { Badge } from "../../Components/Card";

/** Purchase report — paginated purchases with totals. */
function PurchasesReport({ rows, totals }) {
    const summary = [
        { label: "Purchases", value: totals.count },
        { label: "Total", value: money(totals.total), tone: "primary" },
        { label: "Paid", value: money(totals.paid), tone: "success" },
        { label: "Due", value: money(totals.due), tone: "danger" },
    ];

    const columns = [
        { key: "date", label: "Date", render: (r) => date(r.date) },
        {
            key: "invoice_no",
            label: "Invoice",
            render: (r) => (
                <code className="rounded bg-surface-muted px-1.5 py-0.5 text-xs">
                    {r.invoice_no}
                </code>
            ),
        },
        { key: "supplier", label: "Supplier" },
        {
            key: "total",
            label: "Total",
            align: "right",
            render: (r) => (
                <span className="font-medium">{money(r.total)}</span>
            ),
        },
        {
            key: "paid",
            label: "Paid",
            align: "right",
            render: (r) => (
                <span className="text-success">{money(r.paid)}</span>
            ),
        },
        {
            key: "due",
            label: "Due",
            align: "right",
            render: (r) => (
                <span className={r.due > 0 ? "text-danger" : "text-muted"}>
                    {money(r.due)}
                </span>
            ),
        },
        {
            key: "status",
            label: "Status",
            align: "center",
            render: (r) => (
                <Badge tone={r.status_tone || "default"}>{r.status}</Badge>
            ),
        },
    ];

    return (
        <ReportShell
            title="Purchase Report"
            subtitle="Purchases from suppliers over a period."
            breadcrumb={[
                { label: "Reports", href: "/fish-farm/reports" },
                { label: "Purchase Report" },
            ]}
            filterAction="reports.purchases"
            summary={summary}
            columns={columns}
            rows={{ ...rows, title: "Purchases" }}
            exportReport="reports.export"
            exportParams={{ report: "purchases" }}
            emptyTitle="No purchases in this period"
            emptyMessage="Widen the date range, or record a purchase."
        />
    );
}

export default withLayout(PurchasesReport, "Purchase Report");
