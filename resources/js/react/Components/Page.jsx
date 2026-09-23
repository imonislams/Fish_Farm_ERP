import React from "react";
import { usePage } from "@inertiajs/react";
import AppLayout from "../Layouts/AppLayout";

/**
 * Page — wraps a page component in the global AppLayout.
 *
 * Usage in a page module:
 *   export default Page(() => ( <>…</> ), { title: 'Sales' });
 *   // or, if you need the layout applied:
 *   Sales.layout = (page) => <AppLayout title="Sales">{page}</AppLayout>;
 *
 * This thin helper keeps every page consistent (sidebar + header + footer) without
 * repeating the shell markup.
 */
export default function Page(Component, { title } = {}) {
    const Wrapped = (props) => (
        <AppLayout title={title}>
            <Component {...props} />
        </AppLayout>
    );
    Wrapped.displayName = Component.displayName || Component.name || "Page";
    return Wrapped;
}

/** Attach the AppLayout to a page component (Inertia v2 layout convention). */
export function withLayout(Component, title) {
    Component.layout = (page) => <AppLayout title={title}>{page}</AppLayout>;
    return Component;
}

/**
 * usePermission — DISPLAY-ONLY permission check for React pages.
 *
 * Returns a `can(key)` function backed by the shared `auth.user` prop (the same
 * data the sidebar filters on). This decides whether a BUTTON is shown; it is
 * never security. Laravel middleware (`permission:*`) and policies remain the
 * real enforcement (docs/PERMISSIONS.md).
 */
export function usePermission() {
    const { props } = usePage();
    const user = props.auth?.user;
    const permissions = user?.permissions || [];

    return (permission) =>
        !permission || user?.is_super_admin || permissions.includes(permission);
}

/**
 * Format a money value with the configured currency symbol, e.g. "৳124,000.00".
 *
 * ONE formatter for the whole ERP. The VALUE is always a bare number from Laravel;
 * only the symbol/decimals come from the shared `currency` prop. Handles positive,
 * negative (sign before the digits), zero, null and decimals — never produces a
 * doubled symbol or a stray character.
 */
export function money(value, symbol) {
    const s = symbol ?? currentCurrencySymbol;
    const n = Number(value);
    const safe = Number.isFinite(n) ? n : 0;
    const body = Math.abs(safe).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    return `${s}${safe < 0 ? "-" : ""}${body}`;
}

// Set once per Inertia page load from the shared `currency` prop so callers can
// use money(value) without threading the symbol through every component.
let currentCurrencySymbol = "৳";
export function setCurrencySymbol(symbol) {
    if (symbol) currentCurrencySymbol = symbol;
}

/** Format a plain number with fixed decimals. */
export function num(value, decimals = 2) {
    return Number(value ?? 0).toLocaleString("en-US", {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

/** Format an ISO date string as "dd Mmm yyyy" (or '—'). */
export function date(value) {
    if (!value) return "—";
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
}
