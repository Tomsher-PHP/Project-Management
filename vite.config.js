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
                'resources/js/modules/task-list-create.js',
                'resources/js/modules/task-detail.js',
                'resources/js/modules/projects/project-tasks.js',
                'resources/js/modules/task-list-subtasks.js',
                'resources/js/modules/users/user-notification-settings.js',
                'resources/js/modules/users/general-settings.js',
                'resources/js/modules/users/change-password.js',
                'resources/js/modules/tasks/kanban-board.js',
                'resources/js/modules/tasks/time-log-change-request.js',
                'resources/js/modules/users/user-edit.js',
                'resources/js/modules/kpi-form.js',
                'resources/js/modules/checklist-template-form.js',
                'resources/js/modules/projects/project-payment.js',
                'resources/js/modules/workspace/user-timeline.js',
                'resources/js/modules/workspace/workspace-auto-refresh.js',
                'resources/js/modules/workspace/workspace-kanban-filters.js',
                'resources/js/modules/workspace/workspace-user-selector.js',
                'resources/js/modules/tasks/handoff.js',

                'resources/css/modules/user-timeline.css',
                'resources/css/modules/kanban.css',
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
