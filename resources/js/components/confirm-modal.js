/**
 * Confirmation modal — replaces window.confirm() for destructive actions.
 *
 * Any <form> can opt in declaratively:
 *   <form method="POST" action="..." data-confirm="Delete this pond?">
 *
 * We intercept submit, show the shared modal (#confirm-modal), and only submit
 * the form again once the user explicitly confirms.
 */
export function initConfirmModal(root = document) {
    const modal = root.getElementById("confirm-modal");
    if (!modal) return;

    const titleEl = modal.querySelector("#confirm-modal-title");
    const messageEl = modal.querySelector("#confirm-modal-message");
    const acceptBtn = modal.querySelector("#confirm-modal-accept");
    const iconEl = modal.querySelector("#confirm-modal-icon");

    let pendingForm = null;

    const close = () => {
        modal.hidden = true;
        pendingForm = null;
    };

    const open = ({ title, message, confirmLabel, variant }) => {
        if (titleEl) titleEl.textContent = title || "Please confirm";
        if (messageEl)
            messageEl.textContent = message || "This action cannot be undone.";
        if (acceptBtn) {
            acceptBtn.textContent = confirmLabel || "Confirm";
            acceptBtn.className =
                variant === "danger"
                    ? "inline-flex items-center justify-center rounded-control bg-danger px-2.5 py-1.5 text-xs font-medium text-white hover:opacity-95"
                    : "gradient-primary inline-flex items-center justify-center rounded-control px-2.5 py-1.5 text-xs font-medium text-white hover:opacity-95";
        }
        if (iconEl) {
            iconEl.className =
                variant === "danger"
                    ? "grid h-10 w-10 shrink-0 place-items-center rounded-full bg-danger-soft text-danger"
                    : "grid h-10 w-10 shrink-0 place-items-center rounded-full bg-info-soft text-info";
        }
        modal.hidden = false;
        acceptBtn?.focus();
    };

    // Intercept any form that declares data-confirm.
    root.addEventListener("submit", (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;
        if (form.dataset.confirmed === "true") return; // already confirmed

        event.preventDefault();
        pendingForm = form;
        open({
            title: form.dataset.confirmTitle,
            message: form.dataset.confirm,
            confirmLabel: form.dataset.confirmLabel,
            variant: form.dataset.confirmVariant || "danger",
        });
    });

    acceptBtn?.addEventListener("click", () => {
        if (!pendingForm) return close();
        pendingForm.dataset.confirmed = "true";
        pendingForm.submit();
        close();
    });

    modal.querySelectorAll("[data-confirm-cancel]").forEach((el) => {
        el.addEventListener("click", close);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !modal.hidden) close();
    });
}
