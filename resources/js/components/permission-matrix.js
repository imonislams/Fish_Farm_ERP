/**
 * Permission matrix helpers.
 *
 * Provides the "Select all" toggle per permission group on the role create/edit
 * pages. Pure UI behaviour — authorization is enforced on the server
 * (docs/PERMISSIONS.md).
 */
export function initPermissionMatrix(root = document) {
    root.querySelectorAll("[data-permission-matrix]").forEach((matrix) => {
        matrix.querySelectorAll("section").forEach((section) => {
            const toggle = section.querySelector("[data-group-toggle]");
            const boxes = Array.from(
                section.querySelectorAll("[data-permission]"),
            );

            if (!toggle || boxes.length === 0) return;

            const sync = () => {
                const checked = boxes.filter((box) => box.checked).length;
                toggle.checked = checked === boxes.length;
                toggle.indeterminate = checked > 0 && checked < boxes.length;
            };

            toggle.addEventListener("change", () => {
                boxes.forEach((box) => {
                    box.checked = toggle.checked;
                });
                sync();
            });

            boxes.forEach((box) => box.addEventListener("change", sync));

            sync();
        });
    });
}
