import React from "react";
import { withLayout, date } from "../../../Components/Page";
import { Badge } from "../../../Components/Card";
import FeedMovementList from "../MovementList";

/** Food Usage list (feed stock OUT, into a pond). */
function UsagesIndex(props) {
    const config = {
        title: "Food Usage",
        subtitle:
            "Feed given to ponds. Each record reduces the feed type's stock.",
        breadcrumb: () => [
            { label: "Food Management" },
            { label: "Food Usage" },
        ],
        listRoute: "feed.usages.index",
        createRoute: "feed.usages.create",
        exportRoute: "feed.usages.export",
        createLabel: "Record Usage",
        createPermission: "feed.usage",
        canDelete: true,
        deletePermission: "feed.usage",
        deleteConfirm:
            "Delete this usage record? The stock it removed will be restored.",
        listTitle: "Usage records",
        filters: [
            {
                name: "pond",
                label: "Pond",
                placeholder: "All ponds",
                options: "pondOptions",
            },
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
            { key: "feed", label: "Feed type" },
            {
                key: "quantity",
                label: "Quantity",
                align: "right",
                render: (r) => <Badge tone="warning">{r.quantity}</Badge>,
            },
        ],
        emptyTitle: "No usage records",
        emptyMessage: "Adjust your filters, or record the first feed usage.",
    };

    return <FeedMovementList {...props} config={config} />;
}

export default withLayout(UsagesIndex, "Food Usage");
