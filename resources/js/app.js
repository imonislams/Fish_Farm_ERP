/**
 * Fish Farm ERP — JavaScript entry point.
 *
 * Layered as:
 *   app.js            → bootstraps everything (this file)
 *   network.js        → online/offline awareness
 *   pwa.js            → service worker registration + updates
 *   components/*.js   → isolated UI interactions
 *
 * Rules (see docs/ARCHITECTURE.md):
 *   - UI interactions only. No business calculations, no data shaping.
 *   - No frontend framework. Vanilla JS + Vite only.
 */
import './bootstrap';

import { initNetworkStatus } from './network';
import { initPwa } from './pwa';
import { initCollapse, initDeclarativeUI } from './components/ui';
import { initFormGuards } from './components/form-guards';
import { initSidebar, sidebar } from './components/sidebar';
import { initToasts } from './components/toast';
import { initConfirmModal } from './components/confirm-modal';
import { initPermissionMatrix } from './components/permission-matrix';

// Expose the sidebar store so declarative markup can call $store.sidebar.*
window.$store = { sidebar };

function boot() {
    initDeclarativeUI();
    initCollapse();
    initSidebar();
    initToasts();
    initConfirmModal();
    initPermissionMatrix();
    initFormGuards();
    initNetworkStatus();
    initPwa();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
