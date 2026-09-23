/**
 * Fish Farm ERP — Inertia + React entry point.
 *
 * This is the SPA entry for MIGRATED pages. It mounts the Inertia app, resolves the
 * React page named in each Inertia response from Laravel, and renders it inside the
 * shared AppLayout. Navigation is handled entirely by Inertia (no custom router,
 * no full-page reloads) — see docs/ERP-INERTIA-MIGRATION.md.
 *
 * The legacy vanilla-JS boot (resources/js/app.js) still serves the not-yet-migrated
 * Blade pages. The two entries coexist during the controlled migration.
 */
import "../css/app.css";

import React from "react";
import { createRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { setCurrencySymbol } from "./react/Components/Page";

const appName = import.meta.env.VITE_APP_NAME || "Fish Farm ERP";

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),

    resolve: (name) =>
        resolvePageComponent(
            `./react/Pages/${name}.jsx`,
            import.meta.glob("./react/Pages/**/*.jsx"),
        ),

    setup({ el, App, props }) {
        // Apply the configured currency symbol once, globally (the shared prop is
        // the single source — no page hard-codes a symbol).
        setCurrencySymbol(props.initialPage?.props?.currency?.symbol);

        createRoot(el).render(
            <React.StrictMode>
                <App {...props} />
            </React.StrictMode>,
        );
    },

    // Slim top progress bar during Inertia visits (the "no full reload" feedback).
    progress: {
        color: "#16a34a",
        showSpinner: false,
    },
});
