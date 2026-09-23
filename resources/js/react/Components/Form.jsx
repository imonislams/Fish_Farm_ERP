import React from "react";

/**
 * Form primitives — the React mirror of the Blade form components.
 *
 * Laravel remains the authority for validation: errors arrive as Inertia props
 * (`errors`) and are shown here. Nothing on the client decides validity for real.
 */

export function Field({
    label,
    name,
    required = false,
    hint,
    error,
    children,
    className = "",
}) {
    return (
        <div className={`space-y-1.5 ${className}`}>
            {label && (
                <label
                    htmlFor={name}
                    className="block text-sm font-medium text-text-soft"
                >
                    {label}
                    {required && (
                        <span className="text-danger" aria-hidden="true">
                            {" "}
                            *
                        </span>
                    )}
                </label>
            )}
            {children}
            {hint && !error && <p className="text-xs text-muted">{hint}</p>}
            {error && (
                <p
                    className="flex items-center gap-1 text-xs text-danger"
                    role="alert"
                >
                    <svg
                        className="h-3.5 w-3.5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M12 9v4m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"
                        />
                    </svg>
                    {error}
                </p>
            )}
        </div>
    );
}

const controlBase =
    "w-full rounded-control border bg-surface px-3 py-2 text-sm text-text transition-colors " +
    "placeholder:text-muted disabled:bg-surface-muted focus:outline-none focus:ring-2 " +
    "focus:ring-primary/40 focus:border-primary";

const controlState = (hasError) =>
    hasError
        ? "border-danger focus:border-danger focus:ring-danger/30"
        : "border-border-strong";

export function Input({ error, className = "", ...props }) {
    return (
        <input
            className={`${controlBase} ${controlState(error)} ${className}`}
            aria-invalid={error ? "true" : undefined}
            {...props}
        />
    );
}

export function Textarea({ error, className = "", rows = 3, ...props }) {
    return (
        <textarea
            rows={rows}
            className={`${controlBase} ${controlState(error)} ${className}`}
            aria-invalid={error ? "true" : undefined}
            {...props}
        />
    );
}

export function Select({
    error,
    options = [],
    placeholder,
    className = "",
    children,
    ...props
}) {
    // Accept both [value => label] objects and arrays of { value, label }.
    const normalised = Array.isArray(options)
        ? options.map((o) =>
              o && typeof o === "object"
                  ? { value: o.value, label: o.label }
                  : { value: o, label: o },
          )
        : Object.entries(options || {}).map(([value, label]) => ({
              value,
              label,
          }));

    return (
        <select
            className={`${controlBase} ${controlState(error)} ${className}`}
            aria-invalid={error ? "true" : undefined}
            {...props}
        >
            {placeholder && <option value="">{placeholder}</option>}
            {normalised.map((o) => (
                <option key={String(o.value)} value={o.value}>
                    {o.label}
                </option>
            ))}
            {children}
        </select>
    );
}

export function DatePicker({ error, className = "", ...props }) {
    return <Input type="date" error={error} className={className} {...props} />;
}

/** Decimal-friendly number input (price/quantity). Laravel still validates. */
export function NumberInput({
    step = "0.01",
    error,
    className = "",
    ...props
}) {
    return (
        <Input
            type="number"
            inputMode="decimal"
            step={step}
            error={error}
            className={`text-right ${className}`}
            {...props}
        />
    );
}
