import React from "react";
import Icon from "./Icon";

/** Card — panel with optional title/subtitle and an actions area. Mirrors <x-card.card>. */
export function Card({
    title,
    subtitle,
    actions,
    padded = true,
    tone = "default",
    children,
    className = "",
}) {
    const border =
        tone === "danger"
            ? "border-danger/40"
            : tone === "warning"
              ? "border-warning/40"
              : "border-border";

    return (
        <section
            className={`surface-card overflow-hidden border ${border} ${className}`}
        >
            {(title || actions) && (
                <header className="flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 py-3">
                    <div className="min-w-0">
                        {title && (
                            <h3 className="truncate text-sm font-semibold text-text">
                                {title}
                            </h3>
                        )}
                        {subtitle && (
                            <p className="mt-0.5 text-xs text-muted">
                                {subtitle}
                            </p>
                        )}
                    </div>
                    {actions && (
                        <div className="flex flex-wrap items-center gap-2">
                            {actions}
                        </div>
                    )}
                </header>
            )}
            <div className={padded ? "p-4" : ""}>{children}</div>
        </section>
    );
}

const BADGE_TONES = {
    default: "bg-surface-muted text-text-soft",
    primary: "bg-primary/10 text-primary",
    success: "bg-success-soft text-success",
    warning: "bg-warning-soft text-warning",
    danger: "bg-danger-soft text-danger",
    info: "bg-info-soft text-info",
};

/** Badge — status pill. `dot` adds a leading dot. */
export function Badge({
    tone = "default",
    dot = false,
    children,
    className = "",
}) {
    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ${BADGE_TONES[tone] || BADGE_TONES.default} ${className}`}
        >
            {dot && (
                <span
                    className="h-1.5 w-1.5 rounded-full bg-current"
                    aria-hidden="true"
                />
            )}
            {children}
        </span>
    );
}

const KPI_TONES = {
    primary: "text-primary",
    success: "text-success",
    warning: "text-warning",
    danger: "text-danger",
    default: "text-text",
};

/** KpiCard — the compact dashboard summary tile used across every module. */
export function KpiCard({ label, value, hint, icon, tone = "primary" }) {
    const accent = {
        primary: "bg-primary/10 text-primary",
        success: "bg-success-soft text-success",
        warning: "bg-warning-soft text-warning",
        danger: "bg-danger-soft text-danger",
        default: "bg-surface-muted text-text-soft",
    }[tone] || "bg-primary/10 text-primary";

    return (
        <div className="surface-card p-3.5">
            <div className="flex items-center justify-between gap-2">
                <p className="truncate text-xs font-medium uppercase tracking-wide text-muted">
                    {label}
                </p>
                {icon && (
                    <span
                        className={`grid h-7 w-7 shrink-0 place-items-center rounded-full ${accent}`}
                    >
                        <Icon name={icon} className="h-4 w-4" />
                    </span>
                )}
            </div>
            <p
                className={`mt-1.5 truncate text-xl font-semibold ${KPI_TONES[tone] || KPI_TONES.primary}`}
            >
                {value}
            </p>
            {hint && <p className="mt-0.5 truncate text-xs text-muted">{hint}</p>}
        </div>
    );
}

export default Card;
