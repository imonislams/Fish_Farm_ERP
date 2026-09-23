import React, { useState } from "react";
import { usePage } from "@inertiajs/react";
import Sidebar from "../Components/Sidebar";
import Header from "../Components/Header";
import { ToastProvider } from "../Components/Toast";
import { ConfirmProvider } from "../Components/ConfirmModal";

/**
 * AppLayout — the global ERP shell (brief §12).
 *
 *   ┌─────────────────────────────────────────────┐
 *   │ Header / title / notifications / user        │
 *   ├──────────────┬──────────────────────────────┤
 *   │  Sidebar     │   React workspace (page)     │
 *   └──────────────┴──────────────────────────────┘
 *
 * Wraps every migrated page. Sidebar + Header sit OUTSIDE the swapped content, so
 * Inertia page transitions never remount them (no flicker, no lost scroll state).
 */
export default function AppLayout({ children, title }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { props } = usePage();

    return (
        <ToastProvider>
            <ConfirmProvider>
                <a href="#main-content" className="sr-only-focusable">
                    Skip to main content
                </a>

                <div className="flex min-h-screen">
                    <Sidebar
                        open={sidebarOpen}
                        onClose={() => setSidebarOpen(false)}
                    />

                    <div className="flex min-w-0 flex-1 flex-col lg:pl-64">
                        <Header
                            onOpenSidebar={() => setSidebarOpen(true)}
                            title={title}
                        />

                        <main
                            id="main-content"
                            className="flex-1 px-4 py-5 sm:px-6 lg:px-8"
                        >
                            {children}
                        </main>

                        <footer className="border-t border-border px-4 py-4 text-xs text-muted sm:px-6 lg:px-8">
                            {props.app?.name || "Fish Farm ERP"} ©{" "}
                            {new Date().getFullYear()}
                            <span className="mx-1">·</span>
                            {props.app?.version || "v0"}
                        </footer>
                    </div>
                </div>
            </ConfirmProvider>
        </ToastProvider>
    );
}
