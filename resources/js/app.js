/**
 * Fish Farm ERP — legacy Blade entry point.
 *
 * The authenticated application is a full Inertia + React SPA (resources/js/app.jsx).
 * This entry exists for the few remaining server-rendered pages only — the login
 * screen (auth/login.blade.php) and the offline placeholder — and for PWA wiring.
 *
 * It boots ONLY what those pages use:
 *   - server flash toasts          (components/toast.js)
 *   - online/offline awareness     (network.js)
 *   - form submission feedback     (components/form-guards.js)
 *   - service worker registration  (pwa.js)
 *
 * Rules (docs/ARCHITECTURE.md): UI interaction only — no business logic, no data
 * shaping, no framework. Every module is a guarded no-op when its DOM is absent.
 */
import { initNetworkStatus } from './network';
import { initPwa } from './pwa';
import { initToasts } from './components/toast';
import { initFormGuards } from './components/form-guards';
import { initLoginForm } from './components/login';

function boot() {
    initToasts();
    initFormGuards();
    initLoginForm();
    initNetworkStatus();
    initPwa();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
