import React from "react";
import { withLayout, date } from "../../../Components/Page";
import { Badge } from "../../../Components/Card";
import MovementList from "../MovementList";

/** Fish stockings list (stock IN). */
function StockingsIndex(props) {
    const config = {
        title: "Stock In / Stocking",
        subtitle: "Every record of fish placed into a pond.",
        breadcrumb: (routes) => [
            { label: "Fish Stock" },
            { label: "Stock In / Stocking" },
        ],
        listRoute: "fish.stockings.index",
        createRoute: "fish.stockings.create",
        exportRoute: "fish.stockings.export",
        createLabel: "Record Stocking",
        createPermission: "fish.stock",
        canCreate: true,
        canDelete: true,
        deletePermission: "fish.stock",
        deleteConfirm:
            "Delete this stocking record? The fish it added will be removed from stock.",
        listTitle: "Stocking records",
        filters: [
            {
                name: "pond",
                label: "Pond",
                placeholder: "All ponds",
                options: "pondOptions",
            },
            {
                name: "species",
                label: "Species",
                placeholder: "All species",
                options: "speciesOptions",
            },
            { name: "from", label: "From", type: "date" },
            { name: "to", label: "To", type: "date" },
        ],
        columns: [
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
            { key: "species", label: "Species" },
            {
                key: "quantity",
                label: "Quantity",
                align: "right",
                render: (r) => (
                    <Badge tone="primary">
                        {Number(r.quantity).toLocaleString()}
                    </Badge>
                ),
            },
            {
                key: "avg_weight",
                label: "Avg weight",
                align: "right",
                render: (r) => (
                    <span className="whitespace-nowrap">{r.avg_weight}</span>
                ),
            },
            {
                key: "total_weight",
                label: "Total weight",
                align: "right",
                render: (r) => (
                    <span className="whitespace-nowrap">{r.total_weight}</span>
                ),
            },
        ],
        emptyTitle: "No stocking records",
        emptyMessage:
            "Adjust your filters, or record the first stocking into a pond.",
    };

    return <MovementList {...props} config={config} />;
}

export default withLayout(StockingsIndex, "Stock In / Stocking");
