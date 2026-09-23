import React from "react";

/** LoadingState — inline spinner + label for page/section loading. */
export default function LoadingState({ label = "Loading…", className = "" }) {
    return (
        <div
            className={`flex items-center justify-center gap-2 py-12 text-sm text-muted ${className}`}
            role="status"
        >
            <svg
                className="h-5 w-5 animate-spin text-primary"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
            >
                <circle
                    className="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    strokeWidth="4"
                />
                <path
                    className="opacity-90"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                />
            </svg>
            {label}
        </div>
    );
}

/** Skeleton rows for tables while a visit is in flight. */
export function TableSkeleton({ rows = 5, cols = 6 }) {
    return (
        <div className="animate-pulse divide-y divide-border">
            {Array.from({ length: rows }).map((_, r) => (
                <div key={r} className="flex items-center gap-4 px-4 py-3">
                    {Array.from({ length: cols }).map((__, c) => (
                        <div
                            key={c}
                            className="h-3 flex-1 rounded bg-surface-muted"
                        />
                    ))}
                </div>
            ))}
        </div>
    );
}
