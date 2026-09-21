/**
 * Fish Farm ERP — PWA bootstrap.
 *
 * Scope of this foundation (see docs/PWA.md):
 *   1. Register the service worker
 *   2. Cache static assets
 *   3. Provide an offline fallback page
 *   4. Surface network status
 *   5. Apply a safe, user-controlled update strategy
 *
 * Explicitly NOT implemented yet: offline database sync / background mutation
 * queueing. The ERP requires the server for writes and the UI must not imply
 * otherwise.
 */

const SW_URL = "/sw.js";

export function initPwa() {
    if (!("serviceWorker" in navigator)) return;

    window.addEventListener("load", async () => {
        try {
            const registration = await navigator.serviceWorker.register(
                SW_URL,
                { scope: "/" },
            );

            // Safe update strategy: never swap the SW under the user's feet.
            // A new worker waits until the user chooses to refresh.
            registration.addEventListener("updatefound", () => {
                const worker = registration.installing;
                if (!worker) return;

                worker.addEventListener("statechange", () => {
                    if (
                        worker.state === "installed" &&
                        navigator.serviceWorker.controller
                    ) {
                        window.dispatchEvent(
                            new CustomEvent("fishfarm:update-available"),
                        );
                    }
                });
            });
        } catch (error) {
            console.warn(
                "[fishfarm] Service worker registration failed:",
                error,
            );
        }
    });
}
