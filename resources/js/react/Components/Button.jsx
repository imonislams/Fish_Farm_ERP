import React from "react";
import { Link } from "@inertiajs/react";
import Icon from "./Icon";

/**
 * Button — one component for actions and links.
 *
 * - `href` renders an Inertia <Link> (SPA navigation — never a full reload).
 * - `loading` shows a spinner and disables the button (no double submits).
 * - variants mirror the Blade <x-button.button> so both frontends look identical.
 */
const VARIANTS = {
    primary: "gradient-primary text-white hover:opacity-95",
    secondary: "bg-secondary text-white hover:bg-secondary-dark",
    outline:
        "border border-border-strong bg-surface text-text hover:bg-surface-muted",
    ghost: "text-text-soft hover:bg-surface-muted",
    danger: "bg-danger text-white hover:opacity-95",
    success: "bg-success text-white hover:opacity-95",
};

const SIZES = {
    sm: "px-2.5 py-1.5 text-xs gap-1.5",
    md: "px-3.5 py-2 text-sm gap-2",
    lg: "px-4 py-2.5 text-base gap-2",
};

function Spinner() {
    return (
        <svg
            className="h-4 w-4 animate-spin"
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
    );
}

export default function Button({
    as,
    href,
    method,
    children,
    variant = "primary",
    size = "md",
    icon,
    loading = false,
    disabled = false,
    type = "button",
    className = "",
    ...rest
}) {
    const base =
        "inline-flex items-center justify-center rounded-control font-medium transition-colors " +
        "focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 " +
        "disabled:cursor-not-allowed disabled:opacity-60";
    const cls = `${base} ${VARIANTS[variant] || VARIANTS.primary} ${SIZES[size] || SIZES.md} ${className}`;
    const isDisabled = disabled || loading;

    const content = (
        <>
            {loading ? (
                <Spinner />
            ) : icon ? (
                <Icon name={icon} className="h-4 w-4 shrink-0" />
            ) : null}
            {children}
        </>
    );

    if (href && !isDisabled) {
        return (
            <Link href={href} method={method} as={as} className={cls} {...rest}>
                {content}
            </Link>
        );
    }

    if (href && isDisabled) {
        // Disabled link: render as a span so it is genuinely inert.
        return (
            <span
                className={`${cls} pointer-events-none`}
                aria-disabled="true"
                {...rest}
            >
                {content}
            </span>
        );
    }

    return (
        <button type={type} className={cls} disabled={isDisabled} {...rest}>
            {content}
        </button>
    );
}
