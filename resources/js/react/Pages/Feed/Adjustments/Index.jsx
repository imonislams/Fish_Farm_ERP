import React from "react";
import { withLayout, date } from "../../../Components/Page";
import { Badge } from "../../../Components/Card";
import FeedMovementList from "../MovementList";

/** Food Stock Adjustment list (manual stock corrections with a reason). */
function AdjustmentsIndex(props) {
    const config = {
        title: "Food Stock Adjustment",
        subtitle:
            "Manual corrections to feed stock. Every adjustment records its reason.",
        breadcrumb: () => [
            { label: "Food Management" },
            { label: "Stock Adjustment" },
        ],
        listRoute: "feed.adjustments.index",
        createRoute: "feed.adjustments.create",
        createLabel: "Adjust Stock",
        createPermission: "feed.adjust",
        canDelete: true,
        deletePermission: "feed.adjust",
        deleteConfirm: "Delete this adjustment record?",
        listTitle: "Adjustment records",
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
            { key: "feed", label: "Feed type" },
            {
                key: "direction",
                label: "Direction",
                align: "center",
                render: (r) => (
                    <Badge tone={r.is_increase ? "success" : "danger"}>
                        {r.direction}
                    </Badge>
                ),
            },
            {
                key: "quantity",
                label: "Quantity",
                align: "right",
                render: (r) => (
                    <span className="whitespace-nowrap">{r.quantity}</span>
                ),
            },
            { key: "reason", label: "Reason" },
        ],
        emptyTitle: "No adjustment records",
        emptyMessage:
            "Adjust your filters, or record the first stock correction.",
    };

    return <FeedMovementList {...props} config={config} />;
}

export default withLayout(AdjustmentsIndex, "Food Stock Adjustment");
