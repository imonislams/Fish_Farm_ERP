import React from "react";
import { Head, Link } from "@inertiajs/react";
import Icon from "../Components/Icon";

/**
 * Error — the friendly Inertia error page for 403 / 404 / 500 / 503.
 * The real exception is never hidden: it stays in Laravel logs (and the detailed
 * Laravel page in debug mode). This is only the production-facing message.
 */
const COPY = {
    403: {
        title: "Not allowed",
        message: "You do not have permission to view this page.",
        icon: "alert",
    },
    404: {
        title: "Page not found",
        message: "The page you are looking for does not exist.",
        icon: "search",
    },
    500: {
        title: "Something went wrong",
        message: "An unexpected error occurred. The details have been logged.",
        icon: "alert",
    },
    503: {
        title: "Temporarily unavailable",
        message:
            "The application is under maintenance. Please try again shortly.",
        icon: "cog",
    },
};

export default function Error({ status = 500 }) {
    const c = COPY[status] || COPY[500];

    return (
        <>
            <Head title={c.title} />
            <div className="flex min-h-screen flex-col items-center justify-center gap-4 px-6 text-center">
                <span className="grid h-16 w-16 place-items-center rounded-full bg-surface-muted text-muted">
                    <Icon name={c.icon} className="h-8 w-8" />
                </span>
                <p className="text-3xl font-bold text-text">{status}</p>
                <h1 className="text-lg font-semibold text-text">{c.title}</h1>
                <p className="max-w-md text-sm text-text-soft">{c.message}</p>
                <Link
                    href="/dashboard"
                    className="gradient-primary mt-2 rounded-control px-4 py-2 text-sm font-medium text-white"
                >
                    Back to dashboard
                </Link>
            </div>
        </>
    );
}
