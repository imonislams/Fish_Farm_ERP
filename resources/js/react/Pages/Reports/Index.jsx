import React from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import { withLayout } from "../../Components/Page";
import { PageHeader } from "../../Components/PageHeader";
import Icon from "../../Components/Icon";

/** Reports index — one card per report, linking through Inertia. */
function ReportsIndex({ reports = [] }) {
    const { props } = usePage();
    const routes = props.routes || {};

    return (
        <>
            <Head title="Reports" />
            <PageHeader
                title="Reports"
                subtitle="Every report is a real query over existing data, with a CSV export."
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {reports.map((r) => {
                    const href = routes[r.route];
                    const inner = (
                        <>
                            <span className="grid h-10 w-10 place-items-center rounded-control bg-primary/10 text-primary">
                                <Icon name={r.icon} />
                            </span>
                            <div className="min-w-0">
                                <h3 className="truncate text-sm font-semibold text-text">
                                    {r.label}
                                </h3>
                                <p className="mt-0.5 text-xs text-muted">
                                    {r.description}
                                </p>
                            </div>
                        </>
                    );

                    return href ? (
                        <Link
                            key={r.key}
                            href={href}
                            className="surface-card surface-card--hoverable flex items-start gap-3 p-4"
                        >
                            {inner}
                        </Link>
                    ) : (
                        <div
                            key={r.key}
                            className="surface-card flex items-start gap-3 p-4 opacity-60"
                        >
                            {inner}
                        </div>
                    );
                })}
            </div>
        </>
    );
}

export default withLayout(ReportsIndex, "Reports");
