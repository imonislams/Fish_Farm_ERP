/**
 * Sidebar store — controls the mobile drawer.
 * Desktop sidebar is always visible via CSS (lg:translate-x-0).
 */
export const sidebar = {
    open() {
        document
            .getElementById("app-sidebar")
            ?.classList.remove("-translate-x-full");
        document.getElementById("app-sidebar")?.classList.add("translate-x-0");
        document
            .querySelectorAll('[\\@click="$store.sidebar.close()"]')
            .forEach((el) => {
                el.style.display = "block";
            });
    },

    close() {
        document
            .getElementById("app-sidebar")
            ?.classList.add("-translate-x-full");
        document
            .getElementById("app-sidebar")
            ?.classList.remove("translate-x-0");
    },

    toggle() {
        const el = document.getElementById("app-sidebar");
        if (!el) return;
        el.classList.contains("-translate-x-full") ? this.open() : this.close();
    },
};

/** Close the drawer when resizing up to desktop. */
export function initSidebar() {
    window.addEventListener("resize", () => {
        if (window.innerWidth >= 1024) sidebar.close();
    });

    // Escape closes the drawer.
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") sidebar.close();
    });
}
