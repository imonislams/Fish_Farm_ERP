import React from "react";
import { Head, usePage } from "@inertiajs/react";
import { withLayout, money, date, usePermission } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import { Card, KpiCard } from "../../Components/Card";
import { DataTable, Pagination } from "../../Components/DataTable";
import { ReportFilters, ExportButton } from "../../Components/ReportFilters";

/**
 * ReportShell — the shared report layout (brief §27).
 *
 * Header (title + CSV export) → filters → summary KPIs → detailed table → pagination.
 * Every report page composes this so they all behave identically and stay DRY.
 *
 * The CSV export is a plain link to the Laravel export route carrying the CURRENT
 * filters, so the downloaded file matches what is on screen.
 */

// Tailwind only generates classes it can see as complete literal strings, so the
// summary-grid column count is mapped to static class names (never built by
// interpolation). 1–2 fall back to the 2-up layout above.
const SUMMARY_COLS = {
    3: "lg:grid-cols-3",
    4: "lg:grid-cols-4",
    5: "lg:grid-cols-5",
};

export function ReportShell({
    title,
    subtitle,
    breadcrumb,
    filterAction,
    extraFilters,
    summary = [],
    columns,
    rows,
    actions,
    exportReport, // route name of the export, e.g. 'reports.export'
    exportParams = {}, // extra route params, e.g. { report: 'sales' }
    emptyTitle,
    emptyMessage,
    children,
}) {
    const { props, url } = usePage();
    const routes = props.routes || {};
    // DISPLAY-ONLY: hide the export button when the user may not export. The route
    // itself also carries `permission:reports.export`, which is the real enforcement.
    const canExport = usePermission()("reports.export");
    const query = Object.fromEntries(
        new URLSearchParams(url.split("?")[1] || ""),
    );

    const exportHref = routes[exportReport]
        ? Object.entries(exportParams).reduce(
              (acc, [k, v]) => acc.replace(`{${k}}`, v),
              routes[exportReport],
          )
        : "";

    // The report key lives in the path, not the query — drop it from the query
    // string so the exported URL stays clean. Other params (filters) are kept.
    const exportQuery = Object.fromEntries(
        Object.entries({ ...query })
            .filter(([k]) => !(k in exportParams))
            .filter(([, v]) => v !== "" && v !== null && v !== undefined),
    );

    return (
        <>
            <Head title={title} />

            <PageHeader
                title={title}
                subtitle={subtitle}
                breadcrumb={breadcrumb}
                actions={
                    exportHref && canExport ? (
                        <ExportButton
                            href={exportHref}
                            query={exportQuery}
                        />
                    ) : undefined
                }
            />

            {filterAction && (
                <div className="mt-5">
                    <Card title="Period &amp; filters">
                        <ReportFilters
                            action={filterAction}
                            extra={extraFilters}
                        />
                    </Card>
                </div>
            )}

            {summary.length > 0 && (
                <div
                    className={`mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 ${SUMMARY_COLS[summary.length] || "lg:grid-cols-4"}`}
                >
                    {summary.map((s) => (
                        <KpiCard
                            key={s.label}
                            label={s.label}
                            value={s.value}
                            icon={s.icon}
                            tone={s.tone || "primary"}
                        />
                    ))}
                </div>
            )}

            {children}

            {rows && (
                <div className="mt-5">
                    <Card
                        padded={false}
                        title={
                            typeof rows.title === "string"
                                ? rows.title
                                : undefined
                        }
                    >
                        <DataTable
                            columns={columns}
                            rows={rows.data}
                            actions={actions}
                            empty={
                                <div className="px-6 py-12 text-center">
                                    <h4 className="text-sm font-semibold text-text">
                                        {emptyTitle || "No data in this period"}
                                    </h4>
                                    <p className="mt-1 text-sm text-muted">
                                        {emptyMessage ||
                                            "Widen the date range or adjust the filters."}
                                    </p>
                                </div>
                            }
                        />
                        <Pagination paginator={rows} />
                    </Card>
                </div>
            )}

            <p className="mt-3 text-xs text-muted">
                Generated {new Date().toLocaleString("en-GB")}.
            </p>
        </>
    );
}

export { money, date };
