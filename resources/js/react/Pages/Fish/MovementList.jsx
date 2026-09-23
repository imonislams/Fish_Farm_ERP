import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../Components/Page";
import { useConfirm } from "../../Components/ConfirmModal";
import { PageHeader } from "../../Components/PageHeader";
import { Card, Badge } from "../../Components/Card";
import { DataTable, Pagination } from "../../Components/DataTable";
import Button from "../../Components/Button";
import { Field, DatePicker, Select } from "../../Components/Form";
import { ExportButton } from "../../Components/ReportFilters";

/**
 * MovementList — the shared shell for the three fish-stock movement lists
 * (stockings, mortalities, harvests).
 *
 * All three are "a filtered, paginated list of movements with an export and a
 * create action". `config` supplies the labels/columns/routes that differ; the
 * behaviour (server-side filters, Inertia GET, preserved query string) is shared.
 *
 * `config` shape:
 *   { title, subtitle, breadcrumb, listRoute, createRoute, exportRoute,
 *     exportReport, filters: [{name,label,options,placeholder,type}],
 *     columns: [{key,label,align,render}], emptyTitle, emptyMessage,
 *     canCreate, canDelete, info }
 */
function MovementList({ config, rows, filters = {}, options = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [values, setValues] = React.useState(() => {
        const v = {};
        for (const f of config.filters || []) v[f.name] = filters[f.name] ?? "";
        return v;
    });
    const [busy, setBusy] = React.useState(false);

    const set = (name, value) => setValues((v) => ({ ...v, [name]: value }));

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes[config.listRoute] || window.location.pathname,
            values,
            {
                preserveState: true,
                onFinish: () => setBusy(false),
            },
        );
    };

    const clear = () => {
        setBusy(true);
        router.get(
            routes[config.listRoute] || window.location.pathname,
            {},
            { onFinish: () => setBusy(false) },
        );
    };

    const hasFilters = Object.values(values).some((v) => v !== "" && v != null);

    const exportParams = new URLSearchParams(
        Object.entries(values).filter(([, v]) => v !== "" && v != null),
    ).toString();
    const exportHref = config.exportReport
        ? routes["reports.export"]
            ? Object.entries({ report: config.exportReport }).reduce(
                  (acc, [k, v]) => acc.replace(`{${k}}`, v),
                  routes["reports.export"],
              )
            : ""
        : routes[config.exportRoute]
          ? exportParams
              ? `${routes[config.exportRoute]}?${exportParams}`
              : routes[config.exportRoute]
          : "";

    const columns = [
        ...config.columns,
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {r.urls?.pond && (
                        <Button href={r.urls.pond} variant="outline" size="sm">
                            Pond
                        </Button>
                    )}
                    {config.canDelete &&
                        can(config.deletePermission) &&
                        r.urls?.destroy && (
                            <Button
                                variant="danger"
                                size="sm"
                                onClick={async () => {
                                    if (
                                        !(await confirm({ title: "Confirm", description: config.deleteConfirm || "Delete this record?" }))
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
            <Head title={config.title} />

            <PageHeader
                title={config.title}
                subtitle={config.subtitle}
                breadcrumb={config.breadcrumb(routes)}
                actions={
                    <>
                        {routes["fish.index"] && (
                            <Button
                                href={routes["fish.index"]}
                                variant="outline"
                                icon="fish"
                            >
                                Stock Dashboard
                            </Button>
                        )}
                        {exportHref && <ExportButton href={exportHref} />}
                        {config.canCreate &&
                            can(config.createPermission) &&
                            routes[config.createRoute] && (
                                <Button
                                    href={routes[config.createRoute]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    {config.createLabel}
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5">
                <Card title="Search &amp; filters">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5"
                    >
                        {(config.filters || []).map((f) => (
                            <Field key={f.name} label={f.label} name={f.name}>
                                {f.type === "date" ? (
                                    <DatePicker
                                        name={f.name}
                                        value={values[f.name]}
                                        onChange={(e) =>
                                            set(f.name, e.target.value)
                                        }
                                    />
                                ) : (
                                    <Select
                                        name={f.name}
                                        value={values[f.name]}
                                        placeholder={f.placeholder}
                                        options={options[f.options] || {}}
                                        onChange={(e) =>
                                            set(f.name, e.target.value)
                                        }
                                    />
                                )}
                            </Field>
                        ))}

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
                    title={config.listTitle}
                    actions={<Badge tone="info">{rows.total} total</Badge>}
                >
                    <DataTable
                        columns={columns}
                        rows={rows.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    {config.emptyTitle}
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    {config.emptyMessage}
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={rows} />
                </Card>
            </div>

            {config.info && (
                <div className="mt-5 surface-card border border-info/30 p-4 text-sm text-text-soft">
                    {config.info}
                </div>
            )}
        </>
    );
}

export default MovementList;
