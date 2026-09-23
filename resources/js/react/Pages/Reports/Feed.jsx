import React from "react";
import { withLayout, num, date } from "../../Components/Page";
import { ReportShell } from "./ReportShell";
import { Card } from "../../Components/Card";

/** Feed report — purchases and usages over a period, with kg totals. */
function FeedReport({ purchases = [], usages = [], totals }) {
    const summary = [
        {
            label: "Purchased (kg)",
            value: num(totals.purchased_kg, 3),
            tone: "primary",
        },
        { label: "Used (kg)", value: num(totals.used_kg, 3), tone: "warning" },
        {
            label: "Purchase cost",
            value: num(totals.purchase_cost, 2),
            tone: "success",
        },
    ];

    const purchaseCols = [
        { key: "date", label: "Date", render: (r) => date(r.date) },
        { key: "feed", label: "Feed" },
        {
            key: "quantity_kg",
            label: "Quantity (kg)",
            align: "right",
            render: (r) => num(r.quantity_kg, 3),
        },
        {
            key: "total_cost",
            label: "Cost",
            align: "right",
            render: (r) => num(r.total_cost, 2),
        },
    ];

    const usageCols = [
        { key: "date", label: "Date", render: (r) => date(r.date) },
        { key: "feed", label: "Feed" },
        { key: "pond", label: "Pond" },
        {
            key: "quantity_kg",
            label: "Quantity (kg)",
            align: "right",
            render: (r) => num(r.quantity_kg, 3),
        },
    ];

    return (
        <ReportShell
            title="Feed Report"
            subtitle="Feed purchased and consumed over a period."
            breadcrumb={[
                { label: "Reports", href: "/fish-farm/reports" },
                { label: "Feed Report" },
            ]}
            filterAction="reports.feed"
            summary={summary}
            exportReport="reports.export"
            exportParams={{ report: "feed" }}
        >
            <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Card title="Purchases" padded={false}>
                    <SimpleTable
                        columns={purchaseCols}
                        rows={purchases}
                        emptyText="No feed purchased in this period."
                    />
                </Card>
                <Card title="Usages" padded={false}>
                    <SimpleTable
                        columns={usageCols}
                        rows={usages}
                        emptyText="No feed used in this period."
                    />
                </Card>
            </div>
        </ReportShell>
    );
}

function SimpleTable({ columns, rows, emptyText }) {
    if (!rows.length) {
        return (
            <p className="px-4 py-8 text-center text-sm text-muted">
                {emptyText}
            </p>
        );
    }
    return (
        <div className="table-shell">
            <table className="w-full text-sm">
                <thead className="bg-surface-muted text-xs uppercase text-muted">
                    <tr>
                        {columns.map((c) => (
                            <th
                                key={c.key}
                                className={`whitespace-nowrap px-4 py-3 font-medium ${c.align === "right" ? "text-right" : "text-left"}`}
                            >
                                {c.label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-border">
                    {rows.map((r, i) => (
                        <tr
                            key={r.id ?? i}
                            className="hover:bg-surface-muted/60"
                        >
                            {columns.map((c) => (
                                <td
                                    key={c.key}
                                    className={`px-4 py-3 ${c.align === "right" ? "text-right" : ""}`}
                                >
                                    {c.render ? c.render(r) : (r[c.key] ?? "—")}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export default withLayout(FeedReport, "Feed Report");
