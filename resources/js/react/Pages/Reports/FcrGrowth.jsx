import React from "react";
import { withLayout, num, date } from "../../Components/Page";
import { ReportShell } from "./ReportShell";
import { Card, Badge } from "../../Components/Card";

/** FCR & Growth report — growth samples (paginated) + per-pond FCR. */
function FcrGrowthReport({ rows, fcrRows = [], pondOptions }) {
    const options = Object.fromEntries(
        Object.entries(pondOptions || {}).map(([k, v]) => [
            k,
            typeof v === "object" ? v.label : v,
        ]),
    );

    const columns = [
        { key: "date", label: "Date", render: (r) => date(r.date) },
        { key: "pond", label: "Pond" },
        {
            key: "avg_weight_g",
            label: "Avg Weight (g)",
            align: "right",
            render: (r) => r.avg_weight_g ?? "—",
        },
        {
            key: "sample_size",
            label: "Sample Size",
            align: "right",
            render: (r) => r.sample_size ?? "—",
        },
    ];

    const toneFor = (band) =>
        ({
            excellent: "success",
            good: "primary",
            fair: "warning",
            poor: "danger",
        })[band] || "default";

    return (
        <ReportShell
            title="FCR & Growth Report"
            subtitle="Feed conversion ratio and growth samples."
            breadcrumb={[
                { label: "Reports", href: "/fish-farm/reports" },
                { label: "FCR & Growth Report" },
            ]}
            filterAction="reports.fcr-growth"
            extraFilters={[
                {
                    name: "pond",
                    label: "Pond",
                    placeholder: "All ponds",
                    options,
                },
            ]}
            columns={columns}
            rows={{ ...rows, title: "Growth samples" }}
            exportReport="reports.export"
            exportParams={{ report: "fcr-growth" }}
            emptyTitle="No growth samples in this period"
            emptyMessage="Record growth samples to see feed conversion."
        >
            <div className="mt-5">
                <Card title="Feed Conversion Ratio by pond" padded={false}>
                    {fcrRows.length === 0 ? (
                        <p className="px-4 py-8 text-center text-sm text-muted">
                            No ponds found.
                        </p>
                    ) : (
                        <div className="table-shell">
                            <table className="w-full text-sm">
                                <thead className="bg-surface-muted text-xs uppercase text-muted">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium">
                                            Pond
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            FCR
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Feed (kg)
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Gain (kg)
                                        </th>
                                        <th className="px-4 py-3 text-left font-medium">
                                            Note
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {fcrRows.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-4 py-3">
                                                {r.pond}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {r.result?.available ? (
                                                    <Badge
                                                        tone={toneFor(
                                                            r.result.band,
                                                        )}
                                                    >
                                                        {r.result.display}
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted">
                                                        —
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {num(r.result?.feed_kg ?? 0, 3)}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {num(r.result?.gain_kg ?? 0, 3)}
                                            </td>
                                            <td className="px-4 py-3 text-xs text-muted">
                                                {r.result?.reason || "—"}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </ReportShell>
    );
}

export default withLayout(FcrGrowthReport, "FCR & Growth Report");
