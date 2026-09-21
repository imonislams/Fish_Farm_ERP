# PWA — Fish Farm ERP

Read `docs/PROJECT.md` first. See also `docs/UI_GUIDELINES.md` for the network
status components.

---

## 1. What is implemented

This is the **PWA foundation** only. Scope deliberately stops short of offline
data synchronisation.

| Capability                        | Status | Where                                  |
| --------------------------------- | ------ | -------------------------------------- |
| Web app manifest                  | Done   | `public/manifest.webmanifest`          |
| App icons (regular + maskable)    | Done   | `public/icons/**`                      |
| Service worker registration       | Done   | `resources/js/pwa.js`                  |
| Static asset caching              | Done   | `public/sw.js` (cache-first)           |
| Offline fallback page             | Done   | `public/offline.html` (pre-cached)     |
| Navigation caching (read-only)    | Done   | `public/sw.js` (network-first)         |
| Network status indicator          | Done   | `x-layout.network-indicator`           |
| Offline banner + write guard      | Done   | `x-layout.network-status`, `network.js`|
| Safe update strategy              | Done   | waiting worker + explicit `SKIP_WAITING`|
| Theme colour / standalone display | Done   | manifest + `<meta name="theme-color">` |
| Installability metadata           | Done   | manifest + Apple meta tags             |
| **Offline write queue / sync**    | **Not implemented** | — see §6               |
| **Background sync**               | **Not implemented** | — see §6               |
| **Push notifications**            | **Not implemented** | — see §6               |

## 2. Honest offline behaviour

**The ERP requires the server for data.** The service worker must never imply
that a write succeeded while the user is offline.

Rules enforced in code:

1. **Only GET requests are intercepted.** `POST`, `PUT`, `PATCH`, `DELETE` always
   go to the network — the SW does not queue them.
2. **Authenticated responses are never cached.** Requests under `/api/` and
   `/storage/` are skipped entirely, and non-GET is excluded outright.
3. **The UI says so plainly.** The offline banner states that cached pages remain
   viewable but changes cannot be saved.
4. **Write actions can be blocked.** A form marked `data-requires-network` is
   intercepted while offline; `resources/js/components/form-guards.js` prevents
   submission and fires `fishfarm:offline-blocked`, which re-shows the banner.
5. **Nothing is claimed to be saved.** There is no optimistic "Saved!" UI while
   disconnected.

## 3. Caching strategy

| Request type                | Strategy                            | Cached? |
| --------------------------- | ----------------------------------- | ------- |
| CSS/JS/images/fonts         | Cache-first, versioned              | Yes     |
| HTML navigations (GET)      | Network-first, fall back to cache   | Yes (read-only copy of successful pages) |
| Offline fallback page       | Pre-cached on install               | Yes     |
| `/api/*`, `/storage/*`      | Pass-through                        | No      |
| Any non-GET                 | Pass-through                        | No      |
| Cross-origin requests       | Pass-through                        | No      |

Cache versioning: bump `CACHE_VERSION` in `public/sw.js` to invalidate. Old
caches are deleted on `activate`.

## 4. Update strategy (safe)

1. A new service worker installs and **waits** — `skipWaiting()` is not called
   automatically, so the page never changes under the user mid-workflow.
2. `pwa.js` detects `installed` + an existing controller and dispatches
   `fishfarm:update-available`.
3. Applying the update requires an explicit user action: post
   `SKIP_WAITING` to the waiting worker and reload.
4. `clients.claim()` on activate ensures the new worker takes control cleanly.

## 5. Files

```
public/manifest.webmanifest   app identity, icons, shortcuts, display mode
public/sw.js                  service worker (install/activate/fetch/message)
public/offline.html           self-contained offline fallback (no Vite assets)
public/icons/
  icon-192.png                192x192 "any"
  icon-512.png                512x512 "any"
  maskable-512.png            512x512 with safe-zone padding
  apple-touch-icon.png        180x180 for iOS
tools/generate-icons.php      regenerates the icons from the brand gradient
resources/js/pwa.js           registration + update detection
resources/js/network.js       online/offline awareness
resources/views/components/layout/network-indicator.blade.php   header pill
resources/views/components/layout/network-status.blade.php      offline banner
```

### Regenerating icons

```powershell
C:\xampp\php\php.exe tools\generate-icons.php
```

Requires the GD extension. Icons are drawn from the same gradient as the CSS
tokens (`#14532d` → `#15803d` → `#0f766e`), so change both together.

## 6. Future work (explicitly out of scope now)

1. **Offline write queue** — capture mutations in IndexedDB and replay on
   reconnect, with conflict handling. Only viable for a small, carefully chosen
   set of operations (e.g. feed usage, mortality, inspection notes).
2. **Background Sync API** — replay the queue without the tab being open.
3. **Periodic background sync** for dashboard refresh.
4. **Push notifications** — pair with the notifications architecture
   (`docs/MODULES.md`, Notifications) so low-stock and inspection-due alerts can
   be pushed.
5. **Conflict resolution policy** — last-write-wins vs server-authoritative for
   stock mutations. This is a business decision, not just a technical one.
6. **Offline read of authenticated data** — requires an explicit, scoped caching
   policy with a clear expiry and a per-user cache partition. Must not leak data
   between users on a shared device.

None of these should be attempted until the corresponding business modules exist
and the data model is stable.

## 7. Verification checklist

When changing anything PWA-related, confirm:

- [ ] `manifest.webmanifest` is served with `application/manifest+json`.
- [ ] All four icons exist in `public/icons/` and load (200).
- [ ] `sw.js` registers without console errors.
- [ ] DevTools → Application → Manifest shows no errors and the icons render.
- [ ] DevTools → Application → Service Workers shows the worker as activated.
- [ ] Going offline shows the banner and the header pill switches to "Offline".
- [ ] A GET navigation while offline serves the cached page or `offline.html`.
- [ ] A POST while offline is **not** intercepted by the SW.
- [ ] `CACHE_VERSION` was bumped if caching behaviour changed.