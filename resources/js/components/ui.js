/**
 * Fish Farm ERP — UI helpers.
 *
 * Deliberately framework-free (no React/Vue/Alpine/Livewire).
 * Anything here is UI interaction only — never business logic.
 * See docs/ARCHITECTURE.md (JavaScript architecture).
 */

/**
 * Minimal reactivity helper: initialise an element that declares
 * x-data / x-show / x-transition / x-collapse / @click / :class bindings.
 *
 * This is a tiny stand-in for Alpine so the Blade components read declaratively
 * while shipping zero framework bytes. Supported directives are documented in
 * docs/UI_GUIDELINES.md.
 */
export function initDeclarativeUI(root = document) {
    // x-cloak elements become visible once initialised.
    root.querySelectorAll("[x-cloak]").forEach((el) => {
        el.style.display = "";
        el.removeAttribute("x-cloak");
    });

    root.querySelectorAll("[x-data]").forEach((scope) => {
        initScope(scope);
    });
}

function initScope(scope) {
    // Local reactive state declared as x-data="{ open: false }".
    const rawState = scope.getAttribute("x-data") || "{}";
    let state = {};
    try {
        state =
            rawState.trim() === ""
                ? {}
                : new Function(`return (${rawState})`)();
    } catch {
        state = {};
    }

    const proxy = new Proxy(state, {
        set(target, key, value) {
            target[key] = value;
            applyBindings(scope, target);
            return true;
        },
    });

    // @click handlers
    scope.querySelectorAll("[\\@click]").forEach((el) => {
        const expr = el.getAttribute("@click");
        el.addEventListener("click", (event) => {
            try {
                new Function("$event", `with (arguments[1]) { ${expr} }`)(
                    event,
                    proxy,
                );
            } catch (error) {
                console.warn("[fishfarm] click handler failed:", error);
            }
        });
    });

    applyBindings(scope, state);
}

function applyBindings(scope, state) {
    // x-show
    scope.querySelectorAll("[x-show]").forEach((el) => {
        const expr = el.getAttribute("x-show");
        try {
            // eslint-disable-next-line no-new-func
            const result = new Function(
                "$state",
                `with ($state) { return (${expr}); }`,
            )(state);
            el.style.display = result ? "" : "none";
        } catch {
            /* leave as-is */
        }
    });

    // :class="{ 'rotate-90': open }"
    scope.querySelectorAll("[\\:class]").forEach((el) => {
        const expr = el.getAttribute(":class");
        try {
            const result = new Function(
                "$state",
                `with ($state) { return (${expr}); }`,
            )(state);
            if (result && typeof result === "object") {
                Object.entries(result).forEach(([cls, on]) => {
                    el.classList.toggle(cls, Boolean(on));
                });
            } else if (typeof result === "string") {
                el.className = result;
            }
        } catch {
            /* leave as-is */
        }
    });
}

/** Collapse helper for expandable groups. */
export function initCollapse(root = document) {
    root.querySelectorAll("[x-collapse]").forEach((el) => {
        if (el.style.overflow === "hidden") return;
        el.style.overflow = "hidden";
    });
}
