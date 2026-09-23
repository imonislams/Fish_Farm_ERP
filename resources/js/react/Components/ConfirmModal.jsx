import React from "react";
import Modal from "./Modal";
import Button from "./Button";

/**
 * ConfirmModal — the ONLY confirmation dialog in the app (replaces window.confirm).
 *
 * Flow (brief §20):
 *   Delete → ConfirmModal → Confirm → Inertia request → processing state → toast.
 *
 * Prefer the `useConfirm()` hook so pages don't each build their own dialog.
 */
export function ConfirmModal({
    open,
    title = "Are you sure?",
    description,
    confirmLabel = "Confirm",
    cancelLabel = "Cancel",
    tone = "danger",
    processing = false,
    onConfirm,
    onCancel,
}) {
    return (
        <Modal
            open={open}
            onClose={processing ? () => {} : onCancel}
            title={title}
            maxWidth="max-w-md"
            footer={
                <>
                    <Button
                        variant="ghost"
                        onClick={onCancel}
                        disabled={processing}
                    >
                        {cancelLabel}
                    </Button>
                    <Button
                        variant={tone}
                        onClick={onConfirm}
                        loading={processing}
                    >
                        {confirmLabel}
                    </Button>
                </>
            }
        >
            {description && (
                <p className="text-sm text-text-soft">{description}</p>
            )}
        </Modal>
    );
}

export default ConfirmModal;

/**
 * ConfirmProvider — the global, promise-based confirmation dialog for the app.
 *
 * Mounted ONCE by AppLayout, so no page ever renders its own dialog. Replaces
 * every `window.confirm(...)` call with the styled, accessible ConfirmModal:
 *
 *   const confirm = useConfirm();
 *   if (!(await confirm({ title: 'Delete sale?', description: '…' }))) return;
 *   router.delete(...);
 *
 * It is the ONE confirmation implementation in the app (brief §20), built on the
 * same Modal/Button primitives as the rest of the UI.
 */
const ConfirmContext = React.createContext(null);

export function ConfirmProvider({ children }) {
    const [state, setState] = React.useState({
        open: false,
        opts: {},
        resolve: null,
    });

    const confirm = React.useCallback(
        (opts = {}) =>
            new Promise((resolve) =>
                setState({ open: true, opts, resolve }),
            ),
        [],
    );

    const close = React.useCallback((result) => {
        setState((s) => {
            s.resolve?.(result);
            return { open: false, opts: {}, resolve: null };
        });
    }, []);

    const api = React.useMemo(
        () => ({
            confirm,
            modal: (
                <ConfirmModal
                    open={state.open}
                    {...state.opts}
                    onConfirm={() => close(true)}
                    onCancel={() => close(false)}
                />
            ),
        }),
        [confirm, close, state.open, state.opts],
    );

    return (
        <ConfirmContext.Provider value={api}>
            {children}
            {api.modal}
        </ConfirmContext.Provider>
    );
}

/**
 * useConfirm — read the global confirmation dialog.
 *
 *   const { confirm } = useConfirm();
 *   if (!(await confirm({ title: 'Delete?' }))) return;
 *
 * Returns { confirm, modal } where `modal` is already mounted globally, so pages
 * only ever need `confirm`.
 */
export function useConfirm() {
    const ctx = React.useContext(ConfirmContext);
    if (!ctx) throw new Error("useConfirm must be used within <ConfirmProvider>");
    return ctx;
}
