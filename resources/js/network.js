/**
 * Network status awareness (PWA requirement — see docs/PWA.md).
 *
 * Responsibilities:
 *  - Reflect online/offline in the header indicator and the offline banner.
 *  - Warn clearly that writes are unavailable offline (we never pretend a
 *    database change was saved).
 *  - Re-run the wire-up after bfcache restores so the UI is never stale.
 */
export function initNetworkStatus(root = document) {
    const online = root.getElementById("network-indicator-online");
    const offline = root.getElementById("network-indicator-offline");
    const banner = root.getElementById("offline-banner");

    const render = () => {
        const isOnline = navigator.onLine;
        if (online) online.style.display = isOnline ? "flex" : "none";
        if (offline) offline.style.display = isOnline ? "none" : "flex";
        if (banner) banner.hidden = isOnline;
    };

    window.addEventListener("online", render);
    window.addEventListener("offline", render);

    // A blocked action while offline should explain itself.
    window.addEventListener("fishfarm:offline-blocked", () => {
        const banner = root.getElementById("offline-banner");
        if (banner) {
            banner.hidden = false;
            banner.animate?.(
                [
                    { transform: "translateY(0)" },
                    { transform: "translateY(-4px)" },
                    { transform: "translateY(0)" },
                ],
                { duration: 300 },
            );
        }
    });

    // Restore correct state when returning via back/forward cache.
    window.addEventListener("pageshow", (event) => {
        if (event.persisted) render();
    });

    render();
}
