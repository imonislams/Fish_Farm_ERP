/**
 * Toast behaviour.
 *
 * Two sources, one visual language:
 *   1. Server flash messages rendered by <x-toast.toast-stack /> (initial page).
 *   2. Async results — anything may dispatch a `toast` event on document:
 *        document.dispatchEvent(new CustomEvent('toast', {
 *            detail: { tone: 'success', message: 'Pond deleted.' }
 *        }));
 *
 * Manual close via [data-toast-close].
 */
const DEFAULT_TIMEOUT = 6000;

const TONES = {
    success: { icon: "M5 13l4 4L19 7", classes: "border-success/25 bg-success-soft text-success" },
    error: { icon: "M6 18L18 6M6 6l12 12", classes: "border-danger/25 bg-danger-soft text-danger" },
    warning: { icon: "M12 9v4m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z", classes: "border-warning/25 bg-warning-soft text-warning" },
    info: { icon: "M12 8h.01M11 12h1v5h1", classes: "border-info/25 bg-info-soft text-info" },
};

export function initToasts(root = document) {
    const stack = root.getElementById("toast-stack");
    if (!stack) return;

    // 1. Server-rendered flash toasts already in the DOM.
    stack.querySelectorAll(".toast").forEach((toast) => armDismiss(toast));

    // 2. Async toasts dispatched at runtime.
    root.addEventListener("toast", (event) => {
        const { tone = "info", message = "" } = event.detail || {};
        if (!message) return;
        push(root, stack, tone, message);
    });
}

/** Build and mount one toast, then arm its auto-dismiss. */
function push(root, stack, tone, message) {
    const spec = TONES[tone] || TONES.info;

    const el = document.createElement("div");
    el.className = `toast pointer-events-auto flex items-start gap-3 rounded-control border p-3 ${spec.classes}`;
    el.dataset.tone = tone;
    el.setAttribute("role", tone === "error" ? "alert" : "status");
    el.innerHTML = `
        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="${spec.icon}" />
        </svg>
        <p class="min-w-0 flex-1 text-sm">${escapeHtml(message)}</p>
        <button type="button" data-toast-close
            class="shrink-0 rounded p-0.5 opacity-70 hover:opacity-100" aria-label="Dismiss">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>`;

    stack.appendChild(el);
    armDismiss(el);
}

/** Wire the close button and the auto-dismiss timer on a toast element. */
function armDismiss(toast) {
    toast
        .querySelector("[data-toast-close]")
        ?.addEventListener("click", () => dismiss(toast));

    // Errors/warnings stay a little longer so they are actually read.
    const tone = toast.dataset.tone;
    const timeout =
        tone === "error" || tone === "warning" ? DEFAULT_TIMEOUT * 1.5 : DEFAULT_TIMEOUT;

    setTimeout(() => dismiss(toast), timeout);
}

function dismiss(toast) {
    toast.style.transition = "opacity .2s ease, transform .2s ease";
    toast.style.opacity = "0";
    toast.style.transform = "translateX(0.5rem)";
    setTimeout(() => toast.remove(), 200);
}

function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value;

    return div.innerHTML;
}
