window.FilterDrawer = {

    open() {

        const wrapper = document.getElementById('filterDrawerWrapper');
        const drawer = document.getElementById('filterDrawer');

        wrapper.classList.remove('hidden');

        setTimeout(() => {
            drawer.classList.remove('translate-x-full');
        }, 10);

    },

    close() {

        const wrapper = document.getElementById('filterDrawerWrapper');
        const drawer = document.getElementById('filterDrawer');

        drawer.classList.add('translate-x-full');

        setTimeout(() => {
            wrapper.classList.add('hidden');
        }, 300);

    }

};