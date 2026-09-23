import React, { useEffect } from "react";
import Icon from "./Icon";

/**
 * Modal — accessible, responsive dialog shell.
 *
 * Closes on Escape and on backdrop click. Traps nothing fancy but is keyboard-
 * friendly and stops body scroll while open. Used directly, and by ConfirmModal.
 */
export default function Modal({
    open,
    onClose,
    title,
    children,
    footer,
    maxWidth = "max-w-lg",
}) {
    useEffect(() => {
        if (!open) return;
        const onKey = (e) => e.key === "Escape" && onClose?.();
        document.addEventListener("keydown", onKey);
        const prev = document.body.style.overflow;
        document.body.style.overflow = "hidden";
        return () => {
            document.removeEventListener("keydown", onKey);
            document.body.style.overflow = prev;
        };
    }, [open, onClose]);

    if (!open) return null;

    return (
        <div
            className="fixed inset-0 z-[90] flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
            aria-label={title}
        >
            <div
                className={`surface-card w-full ${maxWidth} rounded-t-card sm:rounded-card`}
                onClick={(e) => e.stopPropagation()}
            >
                <header className="flex items-center justify-between gap-3 border-b border-border px-4 py-3">
                    <h3 className="text-sm font-semibold text-text">{title}</h3>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded p-1 text-muted hover:bg-surface-muted hover:text-text"
                        aria-label="Close"
                    >
                        <Icon name="x" className="h-4 w-4" />
                    </button>
                </header>

                <div className="max-h-[70vh] overflow-y-auto p-4">
                    {children}
                </div>

                {footer && (
                    <footer className="flex flex-wrap items-center justify-end gap-2 border-t border-border px-4 py-3">
                        {footer}
                    </footer>
                )}
            </div>
        </div>
    );
}
