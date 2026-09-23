import React, {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useRef,
    useState,
} from "react";
import { usePage } from "@inertiajs/react";
import Icon from "./Icon";

/**
 * Toast system — one global provider, used by every page.
 *
 * - `useToast()` returns { success, error, warning, info } helpers.
 * - Laravel/Inertia FLASH messages (shared prop `flash`) are shown automatically,
 *   so a controller redirect with ->with('success', '…') surfaces as a toast.
 * - Tones: success | error | warning | info. Auto-dismiss; manual close; stacked.
 *
 * This is deliberately the ONLY toast implementation in the app (brief §21).
 */
const ToastContext = createContext(null);

const TONE = {
    success: { bar: "bg-success", icon: "check", ring: "border-success/30" },
    error: { bar: "bg-danger", icon: "alert", ring: "border-danger/30" },
    warning: { bar: "bg-warning", icon: "alert", ring: "border-warning/30" },
    info: { bar: "bg-info", icon: "bell", ring: "border-info/30" },
};

let seq = 0;

export function ToastProvider({ children }) {
    const [toasts, setToasts] = useState([]);
    const timers = useRef({});

    const dismiss = useCallback((id) => {
        setToasts((list) => list.filter((t) => t.id !== id));
        if (timers.current[id]) {
            clearTimeout(timers.current[id]);
            delete timers.current[id];
        }
    }, []);

    const push = useCallback(
        (message, tone = "info") => {
            if (!message) return;
            const id = ++seq;
            setToasts((list) => [...list, { id, message, tone }]);
            timers.current[id] = setTimeout(() => dismiss(id), 5000);
        },
        [dismiss],
    );

    // Callers use these helpers; the API is intentionally tiny.
    const api = useRef({
        success: (m) => push(m, "success"),
        error: (m) => push(m, "error"),
        warning: (m) => push(m, "warning"),
        info: (m) => push(m, "info"),
    });
    api.current.push = push;

    // Show server flash messages on every Inertia visit.
    const { flash } = usePage().props;
    useEffect(() => {
        if (!flash) return;
        if (flash.success) push(flash.success, "success");
        if (flash.error) push(flash.error, "error");
        if (flash.warning) push(flash.warning, "warning");
        if (flash.info) push(flash.info, "info");
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flash?.success, flash?.error, flash?.warning, flash?.info]);

    return (
        <ToastContext.Provider value={api.current}>
            {children}

            <div
                className="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4 sm:items-end sm:pr-6"
                role="region"
                aria-label="Notifications"
                aria-live="polite"
            >
                {toasts.map((t) => {
                    const tone = TONE[t.tone] || TONE.info;
                    return (
                        <div
                            key={t.id}
                            className={`surface-card pointer-events-auto flex w-full max-w-sm items-start gap-3 border-l-4 ${tone.ring} p-3 shadow-lg`}
                            style={{ borderLeftColor: "currentColor" }}
                            role="status"
                        >
                            <span
                                className={`mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full text-white ${tone.bar}`}
                            >
                                <Icon
                                    name={tone.icon}
                                    className="h-3.5 w-3.5"
                                />
                            </span>
                            <p className="flex-1 text-sm text-text">
                                {t.message}
                            </p>
                            <button
                                type="button"
                                onClick={() => dismiss(t.id)}
                                className="rounded p-0.5 text-muted hover:bg-surface-muted hover:text-text"
                                aria-label="Dismiss"
                            >
                                <Icon name="x" className="h-4 w-4" />
                            </button>
                        </div>
                    );
                })}
            </div>
        </ToastContext.Provider>
    );
}

export function useToast() {
    const ctx = useContext(ToastContext);
    if (!ctx) throw new Error("useToast must be used within <ToastProvider>");
    return ctx;
}

export default ToastProvider;
