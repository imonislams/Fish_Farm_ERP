import React, { useState } from "react";
import { router, usePage } from "@inertiajs/react";
import Button from "./Button";
import { Field, DatePicker, Select } from "./Form";

/**
 * ReportFilters — the shared report date-range + extra-filter bar.
 *
 * Mirrors resources/views/components/reports/filters.blade.php. Applying a filter
 * issues an Inertia GET (no full reload); the URL keeps the query string so filters
 * survive refresh / back / bookmarks (brief §24).
 *
 * `extra` is an array of { name, label, options, value, placeholder } selects.
 */
export function ReportFilters({ action, extra = [] }) {
    const { props } = usePage();
    const routes = props.routes || {};
    const q = props.filters || {};

    const [from, setFrom] = useState(q.from || "");
    const [to, setTo] = useState(q.to || "");
    const [values, setValues] = useState(() =>
        Object.fromEntries(extra.map((e) => [e.name, q[e.name] ?? ""])),
    );
    const [processing, setProcessing] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.get(
            routes[action] || window.location.pathname,
            { from, to, ...values },
            { preserveState: true, onFinish: () => setProcessing(false) },
        );
    };

    const reset = () => {
        setProcessing(true);
        router.get(
            routes[action] || window.location.pathname,
            {},
            { onFinish: () => setProcessing(false) },
        );
    };

    return (
        <form
            onSubmit={submit}
            className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
        >
            <Field label="From" name="from">
                <DatePicker
                    name="from"
                    value={from}
                    onChange={(e) => setFrom(e.target.value)}
                />
            </Field>

            <Field label="To" name="to">
                <DatePicker
                    name="to"
                    value={to}
                    onChange={(e) => setTo(e.target.value)}
                />
            </Field>

            {extra.map((f) => (
                <Field key={f.name} label={f.label} name={f.name}>
                    <Select
                        name={f.name}
                        value={values[f.name]}
                        placeholder={
                            f.placeholder || `All ${f.label.toLowerCase()}`
                        }
                        options={f.options || []}
                        onChange={(e) =>
                            setValues((v) => ({
                                ...v,
                                [f.name]: e.target.value,
                            }))
                        }
                    />
                </Field>
            ))}

            <div className="flex items-end gap-2">
                <Button type="submit" variant="primary" loading={processing}>
                    Apply
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    onClick={reset}
                    disabled={processing}
                >
                    Reset
                </Button>
            </div>
        </form>
    );
}

/**
 * ExportButton — a real browser CSV download.
 *
 * The URL is a normal GET to the Laravel export route (which streams a CSV), NOT an
 * Inertia visit — so the browser downloads a file instead of swapping a page. It
 * carries the current filters so the CSV matches what is on screen (brief §28).
 */
export function ExportButton({ href, query = {} }) {
    const qs = new URLSearchParams(
        Object.entries(query).filter(
            ([, v]) => v !== null && v !== undefined && v !== "",
        ),
    ).toString();
    const url = qs ? `${href}?${qs}` : href;

    return (
        <a
            href={url}
            className="inline-flex items-center justify-center gap-2 rounded-control border-border-strong bg-surface px-3.5 py-2 text-sm font-medium text-text transition-colors hover:bg-surface-muted"
        >
            <svg
                className="h-4 w-4"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.8"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"
                />
            </svg>
            Export CSV
        </a>
    );
}

export default ReportFilters;
