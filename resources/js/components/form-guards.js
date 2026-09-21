/**
 * Client-side form validation hints.
 *
 * IMPORTANT: this is a UX convenience only. Laravel server-side validation
 * remains authoritative — never rely on this for correctness or security.
 * See docs/ARCHITECTURE.md and docs/BUSINESS_LOGIC.md.
 */
export function initFormGuards(root = document) {
    root.querySelectorAll("[data-guard-positive]").forEach((input) => {
        input.addEventListener("input", () => {
            const value = Number(input.value);
            const invalid = value < 0 || Number.isNaN(value);
            input.classList.toggle("border-danger", invalid);
            input.setCustomValidity(invalid ? "Value cannot be negative." : "");
        });
    });

    // Guard destructive/offline-hostile actions while disconnected.
    root.querySelectorAll("form[data-requires-network]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (!navigator.onLine) {
                event.preventDefault();
                window.dispatchEvent(
                    new CustomEvent("fishfarm:offline-blocked"),
                );
            }
        });
    });
}
