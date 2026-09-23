import React from "react";
import { withLayout, date } from "../../../Components/Page";
import { Badge } from "../../../Components/Card";
import MovementList from "../MovementList";

/** Fish mortalities list (stock OUT). */
function MortalitiesIndex(props) {
    const config = {
        title: "Mortality",
        subtitle: "Fish deaths recorded per pond.",
        breadcrumb: () => [{ label: "Fish Stock" }, { label: "Mortality" }],
        listRoute: "fish.mortalities.index",
        createRoute: "fish.mortalities.create",
        exportRoute: "fish.mortalities.export",
        createLabel: "Record Mortality",
        createPermission: "fish.mortality",
        canCreate: true,
        canDelete: true,
        deletePermission: "fish.mortality",
        deleteConfirm:
            "Delete this mortality record? The stock it removed will be restored.",
        listTitle: "Mortality records",
        filters: [
            {
                name: "pond",
                label: "Pond",
                placeholder: "All ponds",
                options: "pondOptions",
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
            {
                key: "quantity",
                label: "Quantity",
                align: "right",
                render: (r) => (
                    <Badge tone="warning">
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
            { key: "cause", label: "Cause" },
        ],
        emptyTitle: "No mortality records",
        emptyMessage: "Adjust your filters, or record the first mortality.",
    };

    return <MovementList {...props} config={config} />;
}

export default withLayout(MortalitiesIndex, "Mortality");
