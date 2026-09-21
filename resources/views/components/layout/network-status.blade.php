{{--
 | Network status banner + reconnect notice.
 | Network-aware behaviour for the PWA shell. It never claims data was saved
 | while offline — writes require the server. See docs/PWA.md.
--}}
<div
    id="offline-banner"
    hidden
    class="fixed inset-x-0 bottom-0 z-50 flex items-center justify-center gap-2 bg-warning px-4 py-2 text-sm font-medium text-white"
    role="alert">
    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" />
    </svg>
    <span>
        You are offline. Cached pages remain viewable, but changes cannot be saved
        until the connection returns.
    </span>
</div>