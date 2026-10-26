<div class="space-y-6">

    @include('projects.partials.tabs.history.project-tracking')

    @if ($showTimelineHistory)
        @include('projects.partials.tabs.history.timeline-history')
    @endif

    <!-- ========== Existing History ========== -->
    <div class="grid gap-6 xl:grid-cols-2">

        @include('projects.partials.tabs.history.status-timeline')

        @include('projects.partials.tabs.history.stage-timeline')

    </div>

    @can('project_tracking.create')
        <!-- ========== Project Tracking Create Modal ========== -->
        @include('projects.partials.project-trackings.form', [
            'project' => $project,
        ])
    @endcan

    @include('projects.partials.tabs.history.project-tracking-view-modal')

</div>

<script>
    document.addEventListener('click', function(event) {
        const createButton = event.target.closest(
            '[data-project-tracking-create]'
        );

        if (createButton) {
            const modal = document.querySelector(
                '[data-project-tracking-modal]'
            );

            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            return;
        }

        const closeButton = event.target.closest(
            '[data-project-tracking-modal-close]'
        );

        if (closeButton) {
            const modal = closeButton.closest(
                '[data-project-tracking-modal]'
            );

            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            return;
        }

        const overlay = event.target.closest(
            '[data-project-tracking-modal-overlay]'
        );

        if (overlay) {
            const modal = overlay.closest(
                '[data-project-tracking-modal]'
            );

            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key !== 'Escape') {
            return;
        }

        const modal = document.querySelector(
            '[data-project-tracking-modal]:not(.hidden)'
        );

        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    });

    document.addEventListener('change', function(event) {
        const attachmentInput = event.target.closest(
            '[data-project-tracking-attachments]'
        );

        if (!attachmentInput) {
            return;
        }

        const attachmentError = document.querySelector(
            '[data-project-tracking-attachment-error]'
        );

        if (!attachmentError) {
            return;
        }

        const files = Array.from(attachmentInput.files || []);

        attachmentError.classList.add('hidden');
        attachmentError.textContent = '';

        if (files.length > 5) {
            attachmentError.textContent =
                'You can upload a maximum of 5 attachments.';

            attachmentError.classList.remove('hidden');

            attachmentInput.value = '';
        }
    });
</script>
