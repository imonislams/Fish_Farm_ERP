import React from "react";
import { withLayout, money, date } from "../../Components/Page";
import { ReportShell } from "./ReportShell";

/** Income report — fish sales + other income, with the non-sale entries detailed. */
function IncomeReport({
    rows,
    salesTotal,
    miscTotal,
    incomeTotal,
    categoryOptions,
    filters,
}) {
    const summary = [
        { label: "Fish sales", value: money(salesTotal), tone: "primary" },
        { label: "Other income", value: money(miscTotal), tone: "success" },
        { label: "Total income", value: money(incomeTotal), tone: "success" },
    ];

    // income_categories config is [key => ['label' => ...]] or [key => label]
    const options = Object.fromEntries(
        Object.entries(categoryOptions || {}).map(([k, v]) => [
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
                <span className="font-medium text-success">
                    {money(r.amount)}
                </span>
            ),
        },
        {
            key: "reference",
            label: "Reference",
            render: (r) => r.reference || "—",
        },
    ];

    return (
        <ReportShell
            title="Income Report"
            subtitle="Fish sales plus other income over a period, with the non-sale entries detailed."
            breadcrumb={[
                { label: "Reports", href: "/fish-farm/reports" },
                { label: "Income Report" },
            ]}
            filterAction="reports.income"
            extraFilters={[
                {
                    name: "category",
                    label: "Category",
                    placeholder: "All categories",
                    options,
                },
            ]}
            summary={summary}
            columns={columns}
            rows={{ ...rows, title: "Other income" }}
            exportReport="reports.export"
            exportParams={{ report: "income" }}
            emptyTitle="No other income in this period"
            emptyMessage="Fish sales are listed in the Sales Report; this table shows income that is not a sale."
        />
    );
}

export default withLayout(IncomeReport, "Income Report");
