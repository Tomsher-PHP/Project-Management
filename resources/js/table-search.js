document.addEventListener("input", function (e) {

    if (!e.target.classList.contains("table-search")) return;

    const value = e.target.value.toLowerCase();
    const targetSelector = e.target.dataset.target;

    document.querySelectorAll(targetSelector).forEach((table) => {

        const rows = Array.from(table.tBodies)
            .filter((tbody) => tbody.closest("table") === table)
            .flatMap((tbody) => Array.from(tbody.rows));

        rows.forEach((row) => {
            if (row.dataset.searchDetailsFor) return;

            const projectId = row.dataset.projectRow;
            const detailsRow = projectId
                ? rows.find((candidate) => candidate.dataset.searchDetailsFor === projectId)
                : null;
            const text = `${row.textContent} ${detailsRow?.textContent || ""}`.toLowerCase();
            const show = text.includes(value);

            row.style.display = show ? "" : "none";

            if (detailsRow) {
                detailsRow.style.display = show ? "" : "none";
            }
        });

    });

});
