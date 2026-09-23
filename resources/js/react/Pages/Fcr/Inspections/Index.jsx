import React from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import { withLayout, date, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, DatePicker, Select } from "../../../Components/Form";
import Icon from "../../../Components/Icon";

/** Pond Inspection list — every recorded inspection and its health conclusion. */
function InspectionsIndex({
    inspections,
    options = {},
    filters = {},
    statusCounts = {},
    statuses = {},
}) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [values, setValues] = React.useState({
        pond: filters.pond ?? "",
        status: filters.status ?? "",
        from: filters.from ?? "",
        to: filters.to ?? "",
    });
    const [busy, setBusy] = React.useState(false);

    const set = (k, v) => setValues((s) => ({ ...s, [k]: v }));

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["fcr.inspections.index"] || window.location.pathname,
            values,
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes["fcr.inspections.index"] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = Object.values(values).some((v) => v !== "" && v != null);

    const cardHref = (key) => {
        const base =
            routes["fcr.inspections.index"] || window.location.pathname;
        const params = new URLSearchParams();
        if (filters.pond) params.set("pond", filters.pond);
        if (values.status !== key) params.set("status", key);
        const qs = params.toString();
        return qs ? `${base}?${qs}` : base;
    };

    const columns = [
        {
            key: "date",
            label: "Date",
            render: (r) => (
                <span className="whitespace-nowrap">{date(r.date)}</span>
            ),
        },
        {
            key: "pond",
            label: "Pond",
            render: (r) => (
                <div>
                    <span className="font-medium text-text">{r.pond}</span>
                    {r.pond_number && (
                        <p className="mt-0.5 text-xs text-muted">
                            <code className="rounded bg-surface-muted px-1.5 py-0.5">
                                {r.pond_number}
                            </code>
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: "inspected_by",
            label: "Inspected by",
            render: (r) => (
                <span className="text-text-soft">{r.inspected_by || "—"}</span>
            ),
        },
        {
            key: "health",
            label: "Health",
            align: "center",
            render: (r) => (
                <Badge tone={r.health_tone} dot>
                    {r.health}
                </Badge>
            ),
        },
        {
            key: "action_taken",
            label: "Action taken",
            render: (r) => (
                <span className="text-muted">{r.action_taken || "—"}</span>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    <Button href={r.urls?.show} variant="outline" size="sm">
                        View
                    </Button>
                    {can("fcr.inspection.update") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="ghost" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("fcr.inspection.update") && r.urls?.destroy && (
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={async () => {
                                if (
                                    !(await confirm({ title: "Confirm", description: "Delete this inspection? This cannot be undone." }))
                                )
                                    return;
                                router.delete(r.urls.destroy, {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            Delete
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Pond Inspection" />

            <PageHeader
                title="Pond Inspection"
                subtitle="Every recorded inspection, its readings and the health conclusion reached."
                breadcrumb={[
                    { label: "FCR & Growth" },
                    { label: "Pond Inspection" },
                ]}
                actions={
                    <>
                        {routes["fcr.index"] && (
                            <Button
                                href={routes["fcr.index"]}
                                variant="outline"
                                icon="chart"
                            >
                                FCR Dashboard
                            </Button>
                        )}
                        {can("fcr.inspection.create") &&
                            routes["fcr.inspections.create"] && (
                                <Button
                                    href={
                                        filters.pond
                                            ? `${routes["fcr.inspections.create"]}?pond=${filters.pond}`
                                            : routes["fcr.inspections.create"]
                                    }
                                    variant="secondary"
                                    icon="plus"
                                >
                                    New Inspection
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {Object.entries(statuses).map(([key, meta]) => {
                    const isSelected = values.status === key;
                    return (
                        <Link
                            key={key}
                            href={cardHref(key)}
                            className={`surface-card surface-card--hoverable block border p-4 transition-colors ${
                                isSelected
                                    ? "border-primary ring-1 ring-primary/30"
                                    : ""
                            }`}
                            aria-current={isSelected ? "true" : undefined}
                        >
                            <div className="flex items-center justify-between gap-3">
                                <span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-surface-muted text-muted">
                                    <Icon name="search" className="h-5 w-5" />
                                </span>
                                <Badge tone={meta.tone}>{meta.label}</Badge>
                            </div>
                            <p className="mt-3 text-2xl font-bold text-text">
                                {Number(
                                    statusCounts[key] ?? 0,
                                ).toLocaleString()}
                            </p>
                            <p className="mt-0.5 text-xs text-muted">
                                Inspections
                            </p>
                            <p
                                className={`mt-2 text-xs font-medium ${
                                    meta.concern ? "text-danger" : "text-muted"
                                }`}
                            >
                                {meta.concern
                                    ? "Needs attention"
                                    : "No action flagged"}
                            </p>
                        </Link>
                    );
                })}
            </div>

            <div className="mt-5">
                <Card title="Filters">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5"
                    >
                        <Field label="Pond" name="pond">
                            <Select
                                name="pond"
                                value={values.pond}
                                onChange={(e) => set("pond", e.target.value)}
                                placeholder="All ponds"
                                options={options.pondOptions || {}}
                            />
                        </Field>

                        <Field label="Health status" name="status">
                            <Select
                                name="status"
                                value={values.status}
                                onChange={(e) => set("status", e.target.value)}
                                placeholder="All statuses"
                                options={options.statusOptions || {}}
                            />
                        </Field>

                        <Field label="From" name="from">
                            <DatePicker
                                name="from"
                                value={values.from}
                                onChange={(e) => set("from", e.target.value)}
                            />
                        </Field>

                        <Field label="To" name="to">
                            <DatePicker
                                name="to"
                                value={values.to}
                                onChange={(e) => set("to", e.target.value)}
                            />
                        </Field>

                        <div className="flex items-end gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={busy}
                            >
                                Apply
                            </Button>
                            {hasFilters && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={clear}
                                    disabled={busy}
                                >
                                    Clear
                                </Button>
                            )}
                        </div>
                    </form>
                </Card>
            </div>

            <div className="mt-5">
                <Card
                    padded={false}
                    title="Inspections"
                    actions={
                        <Badge tone="info">{inspections.total} total</Badge>
                    }
                >
                    <DataTable
                        columns={columns}
                        rows={inspections.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No inspections found
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your filters, or record the first
                                    pond inspection.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={inspections} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(InspectionsIndex, "Pond Inspection");
