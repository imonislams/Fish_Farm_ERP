import React from "react";
import Icon from "./Icon";

/** EmptyState — honest "no data" panel. Never invents placeholder rows. */
export default function EmptyState({
    icon = "chart",
    title = "Nothing here yet",
    message,
    action,
}) {
    return (
        <div className="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center">
            <span className="grid h-12 w-12 place-items-center rounded-full bg-surface-muted text-muted">
                <Icon name={icon} className="h-6 w-6" />
            </span>
            <h4 className="text-sm font-semibold text-text">{title}</h4>
            {message && (
                <p className="max-w-md text-sm text-muted">{message}</p>
            )}
            {action && <div className="mt-2">{action}</div>}
        </div>
    );
}
