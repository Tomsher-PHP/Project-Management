document.addEventListener("input", function (e) {

    if (!e.target.classList.contains("table-search")) return;

    const value = e.target.value.toLowerCase();
    const targetSelector = e.target.dataset.target;

    document.querySelectorAll(targetSelector).forEach((table) => {

        table.querySelectorAll("tbody tr").forEach((row) => {

            const text = row.textContent.toLowerCase();

            row.style.display = text.includes(value) ? "" : "none";
        });

    });

});