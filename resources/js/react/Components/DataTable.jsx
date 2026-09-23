import React from "react";
import { Link, router } from "@inertiajs/react";
import Icon from "./Icon";
import EmptyState from "./EmptyState";
import { TableSkeleton } from "./Loading";

/**
 * Pagination — renders Laravel's paginator links through Inertia navigation.
 *
 * Takes the raw paginator object Laravel sends ({ data, links, current_page, … }).
 * Only used when the server paginates; a full dataset is NEVER fetched to the client.
 */
export function Pagination({ paginator }) {
    if (!paginator || !paginator.links || paginator.last_page <= 1) return null;

    const {
        links,
        current_page: current,
        last_page: last,
        from,
        to,
        total,
    } = paginator;

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-border px-4 py-3">
            <p className="text-xs text-muted">
                Showing{" "}
                <span className="font-medium text-text-soft">{from ?? 0}</span>–
                <span className="font-medium text-text-soft">{to ?? 0}</span> of{" "}
                <span className="font-medium text-text-soft">{total ?? 0}</span>
            </p>

            <nav
                className="flex flex-wrap items-center gap-1"
                aria-label="Pagination"
            >
                {links.map((link, i) => {
                    const label = link.label
                        .replace("&laquo;", "")
                        .replace("&raquo;", "")
                        .replace("Previous", "‹")
                        .replace("Next", "›")
                        .trim();
                    const isPrev = link.label.includes("Previous");
                    const isNext = link.label.includes("Next");

                    if (!link.url) {
                        return (
                            <span
                                key={i}
                                className="grid h-8 min-w-8 place-items-center rounded-control px-2 text-xs text-muted opacity-50"
                                dangerouslySetInnerHTML={{
                                    __html: isPrev ? "‹" : isNext ? "›" : label,
                                }}
                            />
                        );
                    }

                    return (
                        <Link
                            key={i}
                            href={link.url}
                            preserveScroll
                            className={`grid h-8 min-w-8 place-items-center rounded-control px-2 text-xs transition-colors ${
                                link.active
                                    ? "gradient-primary font-semibold text-white"
                                    : "border border-border text-text-soft hover:bg-surface-muted"
                            }`}
                            aria-current={link.active ? "page" : undefined}
                            dangerouslySetInnerHTML={{
                                __html: isPrev ? "‹" : isNext ? "›" : label,
                            }}
                        />
                    );
                })}
            </nav>
        </div>
    );
}

/**
 * DataTable — reusable table with responsive horizontal scroll.
 *
 * `columns` : [{ key, label, align?, width?, render?(row) }]
 * `rows`    : array of plain objects
 * `actions` : (row) => ReactNode (row action buttons)
 *
 * Keeps server-side pagination/search/filter intact — this only RENDERS what the
 * server sent. No client-side dataset loading.
 */
export function DataTable({
    columns = [],
    rows = [],
    actions,
    loading = false,
    empty,
    rowKey = (r, i) => r.id ?? i,
}) {
    if (loading)
        return <TableSkeleton cols={columns.length + (actions ? 1 : 0)} />;

    if (!rows.length) {
        return (
            empty || (
                <EmptyState
                    title="No records found"
                    message="Adjust the filters or add a record."
                />
            )
        );
    }

    const align = (a) =>
        a === "right"
            ? "text-right"
            : a === "center"
              ? "text-center"
              : "text-left";

    return (
        <div className="table-shell">
            <table className="w-full text-sm">
                <thead className="bg-surface-muted text-xs uppercase tracking-wide text-muted">
                    <tr>
                        {columns.map((c) => (
                            <th
                                key={c.key}
                                scope="col"
                                className={`whitespace-nowrap px-4 py-3 font-medium ${align(c.align)}`}
                            >
                                {c.label}
                            </th>
                        ))}
                        {actions && (
                            <th
                                scope="col"
                                className="px-4 py-3 text-right font-medium"
                            >
                                Actions
                            </th>
                        )}
                    </tr>
                </thead>
                <tbody className="divide-y divide-border">
                    {rows.map((row, i) => (
                        <tr
                            key={rowKey(row, i)}
                            className="hover:bg-surface-muted/60"
                        >
                            {columns.map((c) => (
                                <td
                                    key={c.key}
                                    className={`px-4 py-3 ${align(c.align)}`}
                                >
                                    {c.render
                                        ? c.render(row)
                                        : (row[c.key] ?? "—")}
                                </td>
                            ))}
                            {actions && (
                                <td className="px-4 py-3">
                                    <div className="table-actions">
                                        {actions(row)}
                                    </div>
                                </td>
                            )}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

/** SearchInput — debounced server-side search (never fires on every keystroke). */
export function SearchInput({
    value = "",
    onChange,
    placeholder = "Search…",
    delay = 350,
    className = "",
}) {
    const [local, setLocal] = React.useState(value);
    const timer = React.useRef(null);

    React.useEffect(() => setLocal(value), [value]);

    const handle = (v) => {
        setLocal(v);
        clearTimeout(timer.current);
        timer.current = setTimeout(() => onChange?.(v), delay);
    };

    return (
        <div className={`relative ${className}`}>
            <span className="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-muted">
                <Icon name="search" className="h-4 w-4" />
            </span>
            <input
                type="search"
                value={local}
                onChange={(e) => handle(e.target.value)}
                placeholder={placeholder}
                aria-label={placeholder}
                className="w-full rounded-control border border-border-strong bg-surface py-2 pl-9 pr-3 text-sm text-text placeholder:text-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/40"
            />
        </div>
    );
}

export default DataTable;
