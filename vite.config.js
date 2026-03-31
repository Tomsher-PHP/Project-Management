import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/modules/team.js',
                'resources/js/modules/schedule-shift.js',
                'resources/js/modules/create-schedule.js',
                'resources/js/modules/customer-contact.js',
                'resources/js/modules/activity-logs.js',
                'resources/js/image-draggable.js',
                'resources/js/modules/projects/project-detail.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
