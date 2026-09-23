import React from "react";
import { withLayout, date } from "../../../Components/Page";
import { Badge } from "../../../Components/Card";
import MovementList from "../MovementList";

/** Harvest list (stock OUT). */
function HarvestsIndex(props) {
    const config = {
        title: "Harvest",
        subtitle: "Fish removed from ponds — sold, transferred or culled.",
        breadcrumb: () => [{ label: "Fish Stock" }, { label: "Harvest" }],
        listRoute: "fish.harvests.index",
        createRoute: "fish.harvests.create",
        exportRoute: "fish.harvests.export",
        createLabel: "Record Harvest",
        createPermission: "fish.harvest",
        canCreate: true,
        canDelete: true,
        deletePermission: "fish.harvest",
        deleteConfirm:
            "Delete this harvest record? The stock it removed will be restored.",
        listTitle: "Harvest records",
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
                    <Badge tone="info">
                        {Number(r.quantity).toLocaleString()}
                    </Badge>
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
            {
                key: "destination",
                label: "Destination",
                render: (r) => (
                    <span className="text-muted">{r.destination || "—"}</span>
                ),
            },
        ],
        emptyTitle: "No harvest records",
        emptyMessage: "Adjust your filters, or record the first harvest.",
    };

    return <MovementList {...props} config={config} />;
}

export default withLayout(HarvestsIndex, "Harvest");
