function initColumnManagers() {

    document.querySelectorAll(".column-manager").forEach((wrapper) => {

        const report = wrapper.dataset.report;
        const STORAGE_KEY = "column_manager_" + report;

        const checkboxes = wrapper.querySelectorAll(".cm-toggle");

        const panel = wrapper.querySelector(".cm-panel");
        const btn = wrapper.querySelector(".cm-btn");

        if (!btn) return;

        btn.addEventListener("click", () => {
            panel.classList.toggle("hidden");
        });

        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || "{}");

        function toggleColumn(column, show) {
            document.querySelectorAll(".col-" + column).forEach(el => {
                el.style.display = show ? "" : "none";
            });
        }

        checkboxes.forEach(cb => {

            const col = cb.dataset.column;

            if (saved[col] === false) {
                cb.checked = false;
                toggleColumn(col, false);
            }

            cb.addEventListener("change", function () {
                toggleColumn(col, this.checked);

                saved[col] = this.checked;
                localStorage.setItem(STORAGE_KEY, JSON.stringify(saved));
            });
        });

        wrapper.querySelector(".cm-select-all")?.addEventListener("click", () => {
            checkboxes.forEach(cb => {
                cb.checked = true;
                cb.dispatchEvent(new Event("change"));
            });
        });

        wrapper.querySelector(".cm-reset")?.addEventListener("click", () => {
            localStorage.removeItem(STORAGE_KEY);

            checkboxes.forEach(cb => {
                cb.checked = true;
                toggleColumn(cb.dataset.column, true);
            });
        });
        
        // close the panel when click outside.
        document.addEventListener("click", function (e) {
            const isInside = wrapper.contains(e.target);

            if (!isInside) {
                panel.classList.add("hidden");
            }
        });

        // close the panel when click ESC button.
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                panel.classList.add("hidden");
            }
        });

    });
    
    
}

// run immediately
initColumnManagers();