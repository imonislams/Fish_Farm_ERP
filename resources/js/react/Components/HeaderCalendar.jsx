import React, { useEffect, useRef, useState } from "react";
import { usePage } from "@inertiajs/react";
import Icon from "./Icon";

/**
 * HeaderCalendar — a compact date next to the notification bell.
 *
 * Shows the current day/month/year (application timezone, from the shared `now`
 * prop) and opens a lightweight month calendar. Days with REAL ERP events get a
 * dot — the events come from /calendar/events (never invented).
 */
const DOW = ["S", "M", "T", "W", "T", "F", "S"];
const MONTHS = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

function parseISO(iso) {
    // Avoid timezone drift: build a local date from Y-M-D parts.
    const [y, m, d] = (iso || "").split("-").map(Number);
    return { y: y || new Date().getFullYear(), m: (m || 1) - 1, d: d || 1 };
}

export default function HeaderCalendar() {
    const { props } = usePage();
    // Application timezone date from the shared prop (falls back to the browser's
    // local date). Using toISOString() here would drift to UTC and can show the
    // wrong day near midnight in a non-UTC timezone (e.g. Asia/Dhaka).
    const todayISO =
        props.now?.date ||
        new Date().toLocaleDateString("en-CA");
    const initial = parseISO(todayISO);

    const [open, setOpen] = useState(false);
    const [year, setYear] = useState(initial.y);
    const [month, setMonth] = useState(initial.m); // 0-based
    const [events, setEvents] = useState({});
    const ref = useRef(null);

    useEffect(() => {
        const onClick = (e) => {
            if (ref.current && !ref.current.contains(e.target)) setOpen(false);
        };
        document.addEventListener("mousedown", onClick);
        return () => document.removeEventListener("mousedown", onClick);
    }, []);

    // Fetch real event dots whenever the visible month changes.
    useEffect(() => {
        if (!open) return;
        let cancelled = false;
        fetch(`/calendar/events?year=${year}&month=${month + 1}`, {
            headers: { Accept: "application/json" },
        })
            .then((r) => r.json())
            .then((data) => {
                if (!cancelled) setEvents(data.events || {});
            })
            .catch(() => {});
        return () => {
            cancelled = true;
        };
    }, [open, year, month]);

    const firstDow = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const cells = [];
    for (let i = 0; i < firstDow; i++) cells.push(null);
    for (let d = 1; d <= daysInMonth; d++) cells.push(d);

    const prev = () => {
        if (month === 0) {
            setMonth(11);
            setYear((y) => y - 1);
        } else setMonth((m) => m - 1);
    };
    const next = () => {
        if (month === 11) {
            setMonth(0);
            setYear((y) => y + 1);
        } else setMonth((m) => m + 1);
    };

    const key = (d) =>
        `${year}-${String(month + 1).padStart(2, "0")}-${String(d).padStart(2, "0")}`;

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                className="flex items-center gap-2 rounded-control px-2.5 py-2 text-sm text-text-soft hover:bg-surface-muted"
                onClick={() => setOpen((v) => !v)}
                aria-haspopup="dialog"
                aria-expanded={open}
            >
                <Icon name="calendar" className="h-4 w-4" />
                <span className="hidden whitespace-nowrap sm:block">
                    {initial.d} {MONTHS[initial.m].slice(0, 3)} {initial.y}
                </span>
            </button>

            {open && (
                <div
                    className="absolute right-0 mt-2 w-72 overflow-hidden rounded-control border border-border bg-surface p-3"
                    style={{ boxShadow: "var(--shadow-dropdown)" }}
                    role="dialog"
                >
                    <div className="mb-2 flex items-center justify-between">
                        <button
                            type="button"
                            className="rounded p-1 text-text-soft hover:bg-surface-muted"
                            onClick={prev}
                            aria-label="Previous month"
                        >
                            <Icon
                                name="chevron"
                                className="h-4 w-4 rotate-180"
                            />
                        </button>
                        <span className="text-sm font-semibold text-text">
                            {MONTHS[month]} {year}
                        </span>
                        <button
                            type="button"
                            className="rounded p-1 text-text-soft hover:bg-surface-muted"
                            onClick={next}
                            aria-label="Next month"
                        >
                            <Icon name="chevron" className="h-4 w-4" />
                        </button>
                    </div>

                    <div className="grid grid-cols-7 gap-1 text-center">
                        {DOW.map((d, i) => (
                            <span
                                key={i}
                                className="py-1 text-[11px] font-medium text-muted"
                            >
                                {d}
                            </span>
                        ))}
                        {cells.map((d, i) => {
                            if (d === null)
                                return <span key={`e${i}`} className="py-1" />;
                            const iso = key(d);
                            const isToday = iso === todayISO;
                            const hasEvent = !!events[iso];
                            return (
                                <button
                                    key={iso}
                                    type="button"
                                    title={
                                        hasEvent
                                            ? events[iso].join(", ")
                                            : undefined
                                    }
                                    className={`relative grid h-8 w-8 place-items-center rounded-control text-xs transition-colors ${
                                        isToday
                                            ? "gradient-primary font-semibold text-white"
                                            : "text-text-soft hover:bg-surface-muted"
                                    }`}
                                >
                                    {d}
                                    {hasEvent && !isToday && (
                                        <span className="absolute bottom-0.5 h-1 w-1 rounded-full bg-primary" />
                                    )}
                                </button>
                            );
                        })}
                    </div>

                    <p className="mt-2 flex items-center gap-1.5 text-[11px] text-muted">
                        <span className="h-1.5 w-1.5 rounded-full bg-primary" />
                        Days with recorded ERP activity
                    </p>
                </div>
            )}
        </div>
    );
}
