import React, { useMemo, useState } from "react";
import { Link, usePage } from "@inertiajs/react";
import Icon from "./Icon";
import { NAVIGATION } from "../navigation";

/**
 * Sidebar — the ERP navigation.
 *
 * - Active route + active submenu derived from the current Inertia URL.
 * - Permission-aware DISPLAY filter (a link is hidden if the user lacks its
 *   permission) — this is UX only; Laravel middleware/policies enforce access.
 * - Collapsible groups, mobile drawer via `onNavigate` (closes the drawer).
 * - All links are Inertia <Link>, so navigation does NOT reload the browser.
 *
 * Routes with no URL yet (module not reachable) render disabled — never a fake link.
 */
export default function Sidebar({ open, onClose }) {
    const { url, props } = usePage();
    const { auth, company, app } = props;
    const user = auth?.user;
    const permissions = user?.permissions || [];

    // CSRF token for the native logout form (the page meta tag set by app.blade.php).
    const csrfToken =
        (typeof document !== "undefined" &&
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content")) ||
        "";

    // Route NAME -> URL, sent per request by Laravel. The frontend never hard-codes URLs.
    const routes = props.routes || {};
    const currentPath = "/" + url.split("?")[0].replace(/^\//, "");

    const can = (permission) =>
        !permission || user?.is_super_admin || permissions.includes(permission);

    const isActive = (routeName) => {
        const target = routes[routeName];
        if (!target) return false;
        const clean = "/" + target.replace(/^\//, "");
        return currentPath === clean;
    };

    const groups = useMemo(() => {
        return NAVIGATION.map((entry) => {
            if (!entry.children) return entry;
            return {
                ...entry,
                children: entry.children.filter((c) => can(c.permission)),
            };
        }).filter((entry) => {
            if (entry.children)
                return entry.children.some((c) => routes[c.route]);
            return can(entry.permission) && routes[entry.route];
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [permissions, routes, currentPath]);

    return (
        <>
            {/* Mobile overlay */}
            {open && (
                <div
                    className="fixed inset-0 z-30 bg-black/50 lg:hidden"
                    onClick={onClose}
                    aria-hidden="true"
                />
            )}

            <aside
                className={`gradient-hero fixed inset-y-0 left-0 z-40 flex w-64 flex-col transition-transform duration-200 lg:translate-x-0 ${
                    open ? "translate-x-0" : "-translate-x-full"
                }`}
                aria-label="Main navigation"
            >
                {/* Brand */}
                <div className="flex h-header shrink-0 items-center gap-3 border-b border-white/10 px-4">
                    <span className="grid h-9 w-9 shrink-0 place-items-center rounded-control bg-white/15 text-sm font-bold text-white">
                        {company?.initials || "FF"}
                    </span>
                    <span className="min-w-0 truncate text-sm font-semibold text-white">
                        {company?.name || app?.name || "Fish Farm"}
                    </span>
                    <button
                        type="button"
                        className="ml-auto rounded p-1 text-white/70 hover:bg-white/10 hover:text-white lg:hidden"
                        onClick={onClose}
                        aria-label="Close navigation"
                    >
                        <Icon name="x" className="h-5 w-5" />
                    </button>
                </div>

                {/* Scrollable menu */}
                <nav
                    className="flex-1 space-y-1 overflow-y-auto px-3 py-4"
                    aria-label="Sidebar"
                >
                    {groups.map((entry) => {
                        const active = entry.children
                            ? entry.children.some((c) => isActive(c.route))
                            : isActive(entry.route);
                        return entry.children ? (
                            <NavGroup
                                key={entry.label}
                                entry={entry}
                                active={active}
                                isActive={isActive}
                                routes={routes}
                                onNavigate={onClose}
                            />
                        ) : (
                            <NavLink
                                key={entry.label}
                                href={routes[entry.route]}
                                icon={entry.icon}
                                label={entry.label}
                                active={active}
                                onNavigate={onClose}
                            />
                        );
                    })}
                </nav>

                {/* Logout */}
                <div className="shrink-0 border-t border-white/10 p-3">
                    {user ? (
                        /*
                         | Logout is a NATIVE form POST, not an Inertia visit.
                         |
                         | POST /logout redirects to the login page, which is a plain
                         | server-rendered Blade screen (NOT an Inertia response). An
                         | Inertia visit follows that 302 with XHR and cannot render a
                         | non-Inertia page inside the SPA, which left the authenticated
                         | shell mounted and duplicated the UI. Submitting a real form
                         | lets the browser follow the redirect itself and land on the
                         | single login screen. Authentication logic is unchanged.
                         */
                        <form method="POST" action="/logout">
                            <input
                                type="hidden"
                                name="_token"
                                value={csrfToken}
                            />
                            <button
                                type="submit"
                                className="flex w-full items-center gap-3 rounded-control px-3 py-2 text-sm font-medium text-sidebar-text transition-colors hover:bg-danger hover:text-white"
                            >
                                <Icon name="logout" />
                                <span>Logout</span>
                            </button>
                        </form>
                    ) : (
                        <Link
                            href="/login"
                            className="flex w-full items-center gap-3 rounded-control px-3 py-2 text-sm font-medium text-sidebar-text transition-colors hover:bg-sidebar-hover hover:text-white"
                        >
                            <Icon name="logout" />
                            <span>Login</span>
                        </Link>
                    )}
                </div>
            </aside>
        </>
    );
}

function NavLink({ href, icon, label, active, onNavigate }) {
    const base =
        "flex items-center gap-3 rounded-control px-3 py-2 text-sm font-medium transition-colors";
    if (!href) {
        return (
            <span
                className={`${base} cursor-not-allowed text-sidebar-muted opacity-60`}
                title="Module not available"
            >
                <Icon name={icon} />
                <span className="truncate">{label}</span>
            </span>
        );
    }
    return (
        <Link
            href={href}
            onClick={onNavigate}
            className={`${base} ${active ? "bg-sidebar-active text-white" : "text-sidebar-text hover:bg-sidebar-hover hover:text-white"}`}
            aria-current={active ? "page" : undefined}
        >
            <Icon name={icon} />
            <span className="truncate">{label}</span>
        </Link>
    );
}

function NavGroup({ entry, active, isActive, routes, onNavigate }) {
    const [open, setOpen] = useState(active);

    React.useEffect(() => {
        if (active) setOpen(true);
    }, [active]);

    return (
        <div>
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="flex w-full items-center gap-3 rounded-control px-3 py-2 text-sm font-medium text-sidebar-text transition-colors hover:bg-sidebar-hover hover:text-white"
                aria-expanded={open}
            >
                <Icon name={entry.icon} />
                <span className="min-w-0 flex-1 truncate text-left">
                    {entry.label}
                </span>
                <Icon
                    name="chevron"
                    className={`h-4 w-4 shrink-0 text-sidebar-muted transition-transform duration-200 ${open ? "rotate-90" : ""}`}
                />
            </button>

            {open && (
                <div className="ml-5 mt-1 space-y-0.5 border-l border-white/10 pl-3">
                    {entry.children.map((child) => {
                        const childActive = isActive(child.route);
                        const href = routes[child.route];
                        if (!href) {
                            return (
                                <span
                                    key={child.label}
                                    className="block cursor-not-allowed rounded-control px-3 py-1.5 text-sm text-sidebar-muted opacity-60"
                                >
                                    {child.label}
                                </span>
                            );
                        }
                        return (
                            <Link
                                key={child.label}
                                href={href}
                                onClick={onNavigate}
                                className={`block rounded-control px-3 py-1.5 text-sm transition-colors ${
                                    childActive
                                        ? "bg-white/10 font-semibold text-white"
                                        : "text-sidebar-muted hover:bg-sidebar-hover hover:text-white"
                                }`}
                                aria-current={childActive ? "page" : undefined}
                            >
                                {child.label}
                            </Link>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
