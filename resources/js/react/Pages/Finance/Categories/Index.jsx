import React from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { withLayout, usePermission } from "../../../Components/Page";
import { useConfirm } from "../../../Components/ConfirmModal";
import { PageHeader } from "../../../Components/PageHeader";
import { Card, Badge } from "../../../Components/Card";
import { DataTable, Pagination } from "../../../Components/DataTable";
import Button from "../../../Components/Button";
import { Field, Input } from "../../../Components/Form";

/** Expense Categories index. */
function CategoriesIndex({ categories, filters = {} }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const can = usePermission();
    const { confirm } = useConfirm();

    const [search, setSearch] = React.useState(filters.search || "");
    const [busy, setBusy] = React.useState(false);

    const apply = (e) => {
        e.preventDefault();
        setBusy(true);
        router.get(
            routes["finance.categories.index"] || window.location.pathname,
            { search },
            { preserveState: true, onFinish: () => setBusy(false) },
        );
    };

    const columns = [
        {
            key: "name",
            label: "Category",
            render: (r) => (
                <span className="font-medium text-text">{r.name}</span>
            ),
        },
        {
            key: "description",
            label: "Description",
            render: (r) => (
                <span className="text-text-soft">{r.description || "—"}</span>
            ),
        },
        {
            key: "expenses_count",
            label: "Expenses",
            align: "right",
            render: (r) => (
                <span className="text-text-soft">{r.expenses_count}</span>
            ),
        },
        {
            key: "is_active",
            label: "Status",
            align: "center",
            render: (r) =>
                r.is_active ? (
                    <Badge tone="success" dot>
                        Active
                    </Badge>
                ) : (
                    <Badge tone="danger" dot>
                        Inactive
                    </Badge>
                ),
        },
        {
            key: "actions",
            label: "Actions",
            align: "right",
            render: (r) => (
                <div className="table-actions">
                    {can("expense.category.manage") && r.urls?.edit && (
                        <Button href={r.urls.edit} variant="ghost" size="sm">
                            Edit
                        </Button>
                    )}
                    {can("expense.category.manage") &&
                        (r.deletable && r.urls?.destroy ? (
                            <Button
                                variant="danger"
                                size="sm"
                                onClick={async () => {
                                    if (
                                        !(await confirm({ title: "Confirm", description: `Delete category "${r.name}"? This cannot be undone.` }))
                                    )
                                        return;
                                    router.delete(r.urls.destroy, {
                                        preserveScroll: true,
                                    });
                                }}
                            >
                                Delete
                            </Button>
                        ) : (
                            <Button
                                href={r.urls?.edit}
                                variant="ghost"
                                size="sm"
                                title="Used by expenses — mark inactive instead"
                            >
                                In use
                            </Button>
                        ))}
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Expense Categories" />

            <PageHeader
                title="Expense Categories"
                subtitle="Classification for recorded expenses. A category in use cannot be deleted."
                breadcrumb={[
                    { label: "Finance" },
                    { label: "Expense Categories" },
                ]}
                actions={
                    <>
                        {routes["finance.expenses.index"] && (
                            <Button
                                href={routes["finance.expenses.index"]}
                                variant="outline"
                                icon="alert"
                            >
                                Expenses
                            </Button>
                        )}
                        {can("expense.category.manage") &&
                            routes["finance.categories.create"] && (
                                <Button
                                    href={routes["finance.categories.create"]}
                                    variant="secondary"
                                    icon="plus"
                                >
                                    New Category
                                </Button>
                            )}
                    </>
                }
            />

            <div className="mt-5">
                <Card title="Search">
                    <form
                        onSubmit={apply}
                        className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div className="sm:col-span-2">
                            <Field label="Search" name="search">
                                <Input
                                    name="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Category name…"
                                />
                            </Field>
                        </div>
                        <div className="flex items-end gap-2">
                            <Button
                                type="submit"
                                variant="primary"
                                loading={busy}
                            >
                                Apply
                            </Button>
                            {search !== "" && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => {
                                        setSearch("");
                                        setBusy(true);
                                        router.get(
                                            routes[
                                                "finance.categories.index"
                                            ] || window.location.pathname,
                                            {},
                                            { onFinish: () => setBusy(false) },
                                        );
                                    }}
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
                    title="All categories"
                    actions={
                        <Badge tone="info">{categories.total} total</Badge>
                    }
                >
                    <DataTable
                        columns={columns}
                        rows={categories.data}
                        empty={
                            <div className="px-6 py-12 text-center">
                                <h4 className="text-sm font-semibold text-text">
                                    No categories found
                                </h4>
                                <p className="mt-1 text-sm text-muted">
                                    Adjust your search, or add the first expense
                                    category.
                                </p>
                            </div>
                        }
                    />
                    <Pagination paginator={categories} />
                </Card>
            </div>
        </>
    );
}

export default withLayout(CategoriesIndex, "Expense Categories");
