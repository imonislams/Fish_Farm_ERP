import React from "react";
import { withLayout, num } from "../../Components/Page";
import { ReportShell } from "./ReportShell";
import { Badge } from "../../Components/Card";

/** Fish Stock report — live stock per pond (no date range; a snapshot). */
function FishStockReport({ rows = [], totalStock, pondOptions }) {
    const summary = [
        {
            label: "Total live fish",
            value: num(totalStock, 0),
            tone: "primary",
        },
    ];

    const options = Object.fromEntries(
        Object.entries(pondOptions || {}).map(([k, v]) => [
            k,
            typeof v === "object" ? v.label : v,
        ]),
    );

    const columns = [
        { key: "pond_number", label: "Pond No" },
        { key: "name", label: "Pond" },
        {
            key: "stock",
            label: "Live Fish",
            align: "right",
            render: (r) => num(r.stock, 0),
        },
        {
            key: "status",
            label: "Status",
            align: "center",
            render: (r) => <Badge>{r.status}</Badge>,
        },
    ];

    // This report is not paginated (a small fixed pond set) — wrap rows as a
    // single-page paginator so ReportShell renders the table + empty state uniformly.
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
            title="Fish Stock Report"
            subtitle="Live stock per pond. A snapshot, so there is no date range."
            breadcrumb={[
                { label: "Reports", href: "/fish-farm/reports" },
                { label: "Fish Stock Report" },
            ]}
            filterAction="reports.fish-stock"
            extraFilters={[
                {
                    name: "pond",
                    label: "Pond",
                    placeholder: "All ponds",
                    options,
                },
            ]}
            summary={summary}
            columns={columns}
            rows={paginated}
            exportReport="reports.export"
            exportParams={{ report: "fish-stock" }}
            emptyTitle="No ponds found"
            emptyMessage="Add a pond to see its stock position."
        />
    );
}

export default withLayout(FishStockReport, "Fish Stock Report");
