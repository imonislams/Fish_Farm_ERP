import React from "react";
import { Link } from "@inertiajs/react";
import Icon from "./Icon";

/**
 * PageHeader — title, subtitle, breadcrumb and an actions area.
 * Mirrors <x-layout.page-header>. Breadcrumb items without a URL are the current page.
 */
export function PageHeader({ title, subtitle, breadcrumb = [], actions }) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-3">
            <div className="min-w-0">
                {breadcrumb.length > 0 && <Breadcrumbs items={breadcrumb} />}
                <h1 className="mt-1 truncate text-xl font-semibold text-text">
                    {title}
                </h1>
                {subtitle && (
                    <p className="mt-0.5 text-sm text-text-soft">{subtitle}</p>
                )}
            </div>
            {actions && (
                <div className="flex flex-wrap items-center gap-2">
                    {actions}
                </div>
            )}
        </div>
    );
}

/** Breadcrumbs — Inertia links, last item is the current (unlinked) page. */
export function Breadcrumbs({ items = [] }) {
    return (
        <nav aria-label="Breadcrumb">
            <ol className="flex flex-wrap items-center gap-1.5 text-xs text-muted">
                {items.map((item, i) => {
                    const last = i === items.length - 1;
                    return (
                        <li
                            key={`${item.label}-${i}`}
                            className="flex items-center gap-1.5"
                        >
                            {item.href && !last ? (
                                <Link
                                    href={item.href}
                                    className="hover:text-primary"
                                >
                                    {item.label}
                                </Link>
                            ) : (
                                <span
                                    className={
                                        last ? "font-medium text-text-soft" : ""
                                    }
                                >
                                    {item.label}
                                </span>
                            )}
                            {!last && (
                                <Icon
                                    name="chevron"
                                    className="h-3 w-3 text-border-strong"
                                />
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}

export default PageHeader;
