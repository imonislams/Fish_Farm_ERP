{{--
 | Network status indicator (header pill).
 | Shows Online / Offline. The real work happens in resources/js/pwa.js,
 | which toggles [hidden] on #network-indicator-online / -offline and keeps
 | the document title + form-submission guard in sync.
--}}
<span class="hidden items-center rounded-full px-2.5 py-1 text-xs font-medium sm:flex"
      role="status" aria-live="polite">
    <span id="network-indicator-online" class="items-center gap-1.5 text-success">
        <span class="h-1.5 w-1.5 rounded-full bg-success"></span>
        Online
    </span>
    <span id="network-indicator-offline" class="items-center gap-1.5 text-danger" style="display:none">
        <span class="h-1.5 w-1.5 rounded-full bg-danger"></span>
        Offline
    </span>
</span>