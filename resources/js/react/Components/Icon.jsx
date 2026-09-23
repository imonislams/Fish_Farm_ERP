/**
 * Icon — the React mirror of resources/views/components/sidebar/icon.blade.php.
 *
 * Icons are inlined on purpose (no icon-font, no CDN). currentColor makes them
 * inherit the surrounding text colour. Keep the path table in sync with the Blade
 * icon component so the two frontends look identical during migration.
 */
const PATHS = {
    grid: "M4 5a1 1 0 011-1h5v7H4V5zm0 9h6v6H5a1 1 0 01-1-1v-5zm10 6h5a1 1 0 001-1v-5h-6v6zm6-15a1 1 0 00-1-1h-5v7h6V5z",
    droplet: "M12 3s6 6.5 6 10.5a6 6 0 11-12 0C6 9.5 12 3 12 3z",
    book: "M4 5a2 2 0 012-2h13v18H6a2 2 0 01-2-2V5zm3 2h9M7 11h9M7 15h6",
    feed: "M5 4h14l-1.5 16h-11L5 4zm5 4v8m4-8v8",
    chart: "M4 20V10m5 10V4m5 16v-7m5 7V8",
    fish: "M4 12c3-5 9-7 14-7-1 2-1 4 0 6-1 2-1 4 0 6-5 0-11-2-14-5zm13.5-2h.01",
    cart: "M3 4h2l2.4 11.2a2 2 0 002 1.6h7.7a2 2 0 002-1.6L21 8H6M9 20h.01M17 20h.01",
    truck: "M3 6h11v10H3V6zm11 4h4l3 3v3h-7v-6zM7 19h.01M17 19h.01",
    users: "M16 20v-1.5a4 4 0 00-4-4H7a4 4 0 00-4 4V20M9.5 10.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM21 20v-1.5a4 4 0 00-3-3.87M16.5 3.75a3.5 3.5 0 010 6.5",
    report: "M7 3h7l5 5v13H7V3zm7 0v5h5M10 13h7M10 17h5",
    cog: "M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM19.4 15a1.7 1.7 0 00.34 1.87l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.7 1.7 0 00-2.9 1.2 2 2 0 11-4 0 1.7 1.7 0 00-2.9-1.2l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.7 1.7 0 003 15a2 2 0 010-4 1.7 1.7 0 001.2-2.9l-.06-.06a2 2 0 112.83-2.83l.06.06A1.7 1.7 0 009 4.6a2 2 0 014 0 1.7 1.7 0 002.9 1.2l.06-.06a2 2 0 112.83 2.83l-.06.06A1.7 1.7 0 0021 11a2 2 0 010 4z",
    logout: "M15 17l5-5-5-5M20 12H9M12 19H6a2 2 0 01-2-2V7a2 2 0 012-2h6",
    bell: "M15 17H9m9 0h1a1 1 0 001-1v-1l-1.5-2V9a5.5 5.5 0 00-11 0v4L6 15v1a1 1 0 001 1h1m7 0a3 3 0 11-6 0",
    menu: "M4 6h16M4 12h16M4 18h16",
    search: "M11 18a7 7 0 100-14 7 7 0 000 14zm5.5-1.5L21 21",
    chevron: "M9 5l7 7-7 7",
    calendar: "M8 3v3m8-3v3M4 8h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z",
    alert: "M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z",
    plus: "M12 5v14M5 12h14",
    download: "M12 3v12m0 0l-4-4m4 4l4-4M5 21h14",
    check: "M5 13l4 4L19 7",
    x: "M6 18L18 6M6 6l12 12",
    trendUp: "M3 17l6-6 4 4 8-8m0 0h-6m6 0v6",
};

export default function Icon({
    name = "grid",
    className = "h-5 w-5 shrink-0",
}) {
    const d = PATHS[name] || PATHS.grid;

    return (
        <svg
            className={className}
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path d={d} />
        </svg>
    );
}
