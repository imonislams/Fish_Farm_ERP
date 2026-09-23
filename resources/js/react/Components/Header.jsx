import React, { useEffect, useRef, useState } from "react";
import { Link, usePage } from "@inertiajs/react";
import Icon from "./Icon";
import NotificationBell from "./NotificationBell";
import HeaderCalendar from "./HeaderCalendar";

/**
 * Header — sticky top bar.
 * Mobile menu toggle, page title, (optional) global search, notifications and the
 * user menu (profile / logout). Internal navigation is Inertia <Link>.
 *
 * Logout is a NATIVE form POST (see the hidden form at the end of the header), not
 * an Inertia visit: POST /logout redirects to the login page, which is a plain
 * server-rendered Blade screen rather than an Inertia response, so it must be
 * followed by a real browser navigation. Submitting it through Inertia left the
 * authenticated shell mounted and duplicated the UI. Authentication is unchanged.
 */
export default function Header({ onOpenSidebar, title }) {
    const { props } = usePage();
    const user = props.auth?.user;
    const [menuOpen, setMenuOpen] = useState(false);
    const menuRef = useRef(null);

    // CSRF token for the native logout form (meta tag set by app.blade.php).
    const csrfToken =
        (typeof document !== "undefined" &&
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content")) ||
        "";

    useEffect(() => {
        const onClick = (e) => {
            if (menuRef.current && !menuRef.current.contains(e.target))
                setMenuOpen(false);
        };
        document.addEventListener("mousedown", onClick);
        return () => document.removeEventListener("mousedown", onClick);
    }, []);

    return (
        <header className="sticky top-0 z-20 flex h-header shrink-0 items-center gap-3 border-b border-border bg-surface px-4 sm:px-6 lg:px-8">
            <button
                type="button"
                className="rounded-control p-2 text-text-soft hover:bg-surface-muted lg:hidden"
                onClick={onOpenSidebar}
                aria-label="Open navigation"
            >
                <Icon name="menu" />
            </button>

            <h1 className="min-w-0 flex-1 truncate text-base font-semibold text-text">
                {title || "Dashboard"}
            </h1>

            <HeaderCalendar />

            <NotificationBell />

            {user && (
                <div className="relative" ref={menuRef}>
                    <button
                        type="button"
                        className="flex items-center gap-2 rounded-control px-2 py-1.5 hover:bg-surface-muted"
                        onClick={() => setMenuOpen((v) => !v)}
                        aria-haspopup="menu"
                        aria-expanded={menuOpen}
                    >
                        <span className="gradient-primary grid h-8 w-8 place-items-center rounded-full text-xs font-bold text-white">
                            {(user.name || "U").charAt(0).toUpperCase()}
                        </span>
                        <span className="hidden truncate text-sm font-medium sm:block">
                            {user.name}
                        </span>
                    </button>

                    {menuOpen && (
                        <div
                            className="absolute right-0 mt-2 w-48 overflow-hidden rounded-control border border-border bg-surface py-1"
                            style={{ boxShadow: "var(--shadow-dropdown)" }}
                            role="menu"
                        >
                            <Link
                                href={
                                    props.routes?.["settings.profile.edit"] ||
                                    "/settings/profile"
                                }
                                className="block px-4 py-2 text-sm text-text-soft hover:bg-surface-muted"
                                role="menuitem"
                                onClick={() => setMenuOpen(false)}
                            >
                                User Profile
                            </Link>
                            <button
                                type="button"
                                className="block w-full px-4 py-2 text-left text-sm text-danger hover:bg-danger-soft"
                                role="menuitem"
                                onClick={() => {
                                    // Submit the native logout form BEFORE the menu
                                    // unmounts this button. Closing the menu first would
                                    // remove the button mid-click and the submit would
                                    // never fire.
                                    document
                                        .getElementById("logout-form")
                                        ?.submit();
                                    setMenuOpen(false);
                                }}
                            >
                                Logout
                            </button>
                        </div>
                    )}
                </div>
            )}

            {/*
             | Native logout form. Kept OUTSIDE the dropdown so it stays in the DOM
             | after the menu closes, and submitted by the Logout menuitem above.
             */}
            <form method="POST" action="/logout" id="logout-form" className="hidden">
                <input type="hidden" name="_token" value={csrfToken} />
            </form>
        </header>
    );
}
