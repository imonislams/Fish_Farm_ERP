import React, { useEffect, useRef, useState } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import Icon from "./Icon";

/**
 * NotificationBell — the header notification centre.
 *
 * The unread COUNT and a small RECENT slice arrive via shared props (lightweight);
 * the "view all" list is fetched from /notifications only when opened. Mark-read
 * actions hit small JSON endpoints and refresh the shared prop — no full reload.
 */
export default function NotificationBell() {
    const { props } = usePage();
    const notifications = props.notifications || { unread: 0, recent: [] };
    const routes = props.routes || {};
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState(notifications.recent || []);
    const ref = useRef(null);

    // Keep the open list in sync with the latest shared prop after an action.
    useEffect(() => {
        setItems(notifications.recent || []);
    }, [notifications.recent]);

    useEffect(() => {
        const onClick = (e) => {
            if (ref.current && !ref.current.contains(e.target)) setOpen(false);
        };
        document.addEventListener("mousedown", onClick);
        return () => document.removeEventListener("mousedown", onClick);
    }, []);

    const unread = notifications.unread || 0;

    const refresh = () => router.reload({ only: ["notifications"] });

    const markRead = (item) => {
        if (item.read) return;
        fetch(`/notifications/${item.id}/read`, {
            method: "PATCH",
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content"),
                Accept: "application/json",
            },
        }).then(() => refresh());
    };

    const markAll = () => {
        fetch("/notifications/read-all", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content"),
                Accept: "application/json",
            },
        }).then(() => refresh());
    };

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                className="relative rounded-control p-2 text-text-soft hover:bg-surface-muted"
                aria-label="Notifications"
                aria-haspopup="menu"
                aria-expanded={open}
                onClick={() => setOpen((v) => !v)}
            >
                <Icon name="bell" />
                {unread > 0 && (
                    <span className="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-danger px-1 text-[10px] font-bold text-white">
                        {unread > 9 ? "9+" : unread}
                    </span>
                )}
            </button>

            {open && (
                <div
                    className="absolute right-0 mt-2 w-80 overflow-hidden rounded-control border border-border bg-surface"
                    style={{ boxShadow: "var(--shadow-dropdown)" }}
                    role="menu"
                >
                    <header className="flex items-center justify-between border-b border-border px-4 py-2.5">
                        <span className="text-sm font-semibold text-text">
                            Notifications
                        </span>
                        {unread > 0 && (
                            <button
                                type="button"
                                className="text-xs font-medium text-primary hover:underline"
                                onClick={markAll}
                            >
                                Mark all read
                            </button>
                        )}
                    </header>

                    <div className="max-h-80 overflow-y-auto">
                        {items.length === 0 ? (
                            <p className="px-4 py-8 text-center text-sm text-muted">
                                No notifications yet.
                            </p>
                        ) : (
                            items.map((n) => (
                                <div
                                    key={n.id}
                                    className={`flex items-start gap-3 border-b border-border px-4 py-3 last:border-0 ${
                                        n.read ? "" : "bg-primary/5"
                                    }`}
                                >
                                    <span className="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full bg-surface-muted text-text-soft">
                                        <Icon
                                            name={n.icon}
                                            className="h-4 w-4"
                                        />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        {n.url ? (
                                            <Link
                                                href={n.url}
                                                className="block truncate text-sm font-medium text-text hover:text-primary"
                                                onClick={() => {
                                                    markRead(n);
                                                    setOpen(false);
                                                }}
                                            >
                                                {n.title}
                                            </Link>
                                        ) : (
                                            <p className="truncate text-sm font-medium text-text">
                                                {n.title}
                                            </p>
                                        )}
                                        {n.description && (
                                            <p className="mt-0.5 line-clamp-2 text-xs text-muted">
                                                {n.description}
                                            </p>
                                        )}
                                        <p className="mt-0.5 text-[11px] text-muted">
                                            {n.at}
                                        </p>
                                    </div>
                                    {!n.read && (
                                        <button
                                            type="button"
                                            className="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-primary"
                                            aria-label="Mark as read"
                                            onClick={() => markRead(n)}
                                        />
                                    )}
                                </div>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
