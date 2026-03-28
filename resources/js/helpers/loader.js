export const Loader = {
    show() {
        const el = document.getElementById("global-loader");
        if (el) el.classList.remove("hidden");
    },
    hide() {
        const el = document.getElementById("global-loader");
        if (el) el.classList.add("hidden");
    },
    toggle() {
        const el = document.getElementById("global-loader");
        if (!el) return;
        el.classList.toggle("hidden");
    }
};