/**
 * Login page interactions — presentation only.
 *
 * Two enhancements to the existing login form, neither of which touches
 * authentication:
 *   1. Show/hide password toggle  (frontend only — the field name, value and
 *      submission are unchanged; only the input `type` flips).
 *   2. Submit state — disables the button and shows "Signing in…" so the form
 *      cannot be submitted twice accidentally.
 *
 * Laravel remains the authority for authentication and validation. This module is
 * a no-op when the login form is absent (docs/ARCHITECTURE.md — UI interaction
 * only, no business logic).
 */
export function initLoginForm(root = document) {
    const form = root.querySelector("[data-login-form]");
    if (!form) return;

    /* ------------------------------------------------ show / hide password -- */
    const password = form.querySelector("[data-password-input]");
    const toggle = form.querySelector("[data-password-toggle]");

    if (password && toggle) {
        const eyeShow = toggle.querySelector("[data-eye-show]");
        const eyeHide = toggle.querySelector("[data-eye-hide]");

        toggle.addEventListener("click", () => {
            const willShow = password.type === "password";
            password.type = willShow ? "text" : "password";

            // Keep the accessible state in step with the visual state.
            toggle.setAttribute("aria-pressed", String(willShow));
            toggle.setAttribute(
                "aria-label",
                willShow ? "Hide password" : "Show password",
            );
            eyeShow?.classList.toggle("hidden", willShow);
            eyeHide?.classList.toggle("hidden", !willShow);

            // Return focus to the field the user was typing in.
            password.focus({ preventScroll: true });
        });
    }

    /* ------------------------------------------------------- submit state -- */
    const submit = form.querySelector("[data-login-submit]");
    const label = form.querySelector("[data-login-label]");
    let submitting = false;

    form.addEventListener("submit", (event) => {
        // Guard against a double submit (double-click / Enter twice).
        if (submitting) {
            event.preventDefault();
            return;
        }

        // Never block a native validation failure from showing its message.
        if (!form.checkValidity()) return;

        submitting = true;

        if (submit) {
            submit.disabled = true;
            submit.setAttribute("aria-busy", "true");
        }

        if (label) {
            label.textContent = "Signing in…";
        }

        // A spinner is injected next to the label — visual only.
        if (submit && !submit.querySelector("[data-login-spinner]")) {
            const spinner = document.createElement("span");
            spinner.setAttribute("data-login-spinner", "");
            spinner.setAttribute("aria-hidden", "true");
            spinner.className =
                "h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white";
            submit.insertBefore(spinner, label || submit.firstChild);
        }
    });
}
