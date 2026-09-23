import React from "react";
import { withLayout, money, date } from "../../../Components/Page";
import { Badge } from "../../../Components/Card";
import FeedMovementList from "../MovementList";

/** Food Purchase list (feed stock IN). */
function PurchasesIndex(props) {
    const config = {
        title: "Food Purchase",
        subtitle:
            "Feed bought into stock. Each record increases the feed type's stock.",
        breadcrumb: () => [
            { label: "Food Management" },
            { label: "Food Purchase" },
        ],
        listRoute: "feed.purchases.index",
        createRoute: "feed.purchases.create",
        exportRoute: "feed.purchases.export",
        createLabel: "Record Purchase",
        createPermission: "feed.purchase",
        canDelete: true,
        deletePermission: "feed.purchase",
        deleteConfirm:
            "Delete this purchase record? The stock it added will be removed.",
        listTitle: "Purchase records",
        filters: [
            {
                name: "type",
                label: "Feed type",
                placeholder: "All feed types",
                options: "typeOptions",
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
                key: "feed",
                label: "Feed type",
                render: (r) => (
                    <div>
                        <span className="font-medium text-text">{r.feed}</span>
                        {r.brand && (
                            <p className="mt-0.5 text-xs text-muted">
                                {r.brand}
                            </p>
                        )}
                    </div>
                ),
            },
            {
                key: "supplier",
                label: "Supplier",
                render: (r) => (
                    <span className="text-muted">{r.supplier || "—"}</span>
                ),
            },
            {
                key: "quantity",
                label: "Quantity",
                align: "right",
                render: (r) => <Badge tone="success">{r.quantity}</Badge>,
            },
            {
                key: "unit_cost",
                label: "Unit cost",
                align: "right",
                render: (r) => (
                    <span className="whitespace-nowrap">
                        {r.unit_cost != null ? money(r.unit_cost) : "—"}
                    </span>
                ),
            },
            {
                key: "total_cost",
                label: "Total",
                align: "right",
                render: (r) => (
                    <span className="whitespace-nowrap">
                        {r.total_cost != null ? money(r.total_cost) : "—"}
                    </span>
                ),
            },
        ],
        emptyTitle: "No purchase records",
        emptyMessage: "Adjust your filters, or record the first feed purchase.",
    };

    return <FeedMovementList {...props} config={config} />;
}

export default withLayout(PurchasesIndex, "Food Purchase");
