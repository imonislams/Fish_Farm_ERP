/**
 * Toast behaviour.
 * Auto-dismisses flash messages rendered by <x-toast.toast-stack />.
 * Manual close via [data-toast-close].
 */
const DEFAULT_TIMEOUT = 6000;

export function initToasts(root = document) {
    const stack = root.getElementById("toast-stack");
    if (!stack) return;

    stack.querySelectorAll(".toast").forEach((toast) => {
        // Manual dismiss
        toast
            .querySelector("[data-toast-close]")
            ?.addEventListener("click", () => dismiss(toast));

        // Auto dismiss (warnings stay longer)
        const tone = toast.dataset.tone;
        const timeout =
            tone === "error" || tone === "warning"
                ? DEFAULT_TIMEOUT * 1.5
                : DEFAULT_TIMEOUT;
        setTimeout(() => dismiss(toast), timeout);
    });
}

function dismiss(toast) {
    toast.style.transition = "opacity .2s ease, transform .2s ease";
    toast.style.opacity = "0";
    toast.style.transform = "translateX(0.5rem)";
    setTimeout(() => toast.remove(), 200);
}
