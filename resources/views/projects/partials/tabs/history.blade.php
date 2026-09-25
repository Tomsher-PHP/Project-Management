<div class="space-y-6">

    {{-- Project Tracking --}}
    <section class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">

            <div>
                <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                    Project Tracking
                </h4>
            </div>

            @can('project_tracking.create')
                <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-success-300 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition duration-200 hover:bg-success-400" data-project-tracking-modal-open>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>

                    <span>Add Tracking</span>
                </button>
            @endcan

        </div>

        <div id="project-tracking-history">
            @include('projects.partials.project-trackings.show', [
                'projectTrackings' => $projectTrackings,
            ])
        </div>

    </section>


    {{-- =========================================================
        Existing History
    ========================================================== --}}
    <div class="grid gap-6 xl:grid-cols-2">

        {{-- Status Timeline --}}
        <section class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">

                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                        Status Timeline
                    </h4>
                </div>

                <div class="flex flex-wrap items-center gap-2">

                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300">

                        <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $currentStatus['color'] }}"></span>

                        {{ $currentStatus['label'] }}

                    </span>

                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300">

                        {{ $statusHistory->count() }}

                        {{ \Illuminate\Support\Str::plural('change', $statusHistory->count()) }}

                    </span>

                </div>

            </div>


            <div class="max-h-[560px] min-h-[320px] overflow-y-auto p-5">

                @if ($statusHistory->isEmpty())

                    <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                        No status changes recorded yet.
                    </div>
                @else
                    <div class="space-y-0">

                        @foreach ($statusHistory as $entry)
                            <div class="relative flex gap-4 pb-6 last:pb-0">

                                @if (!$loop->last)
                                    <span class="absolute left-[9px] top-6 h-full w-px bg-bgray-200 dark:bg-darkblack-400"></span>
                                @endif

                                <span class="relative z-10 mt-1 inline-flex h-5 w-5 shrink-0 rounded-full border-4 border-white dark:border-darkblack-600" style="background-color: {{ $entry['to_color'] }}"></span>

                                <div class="min-w-0 flex-1 rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">

                                    <div class="flex flex-wrap items-center justify-between gap-3">

                                        <div class="flex min-w-0 flex-wrap items-center gap-2">

                                            <span class="inline-flex items-center gap-2 rounded-full bg-bgray-50 px-3 py-1 text-xs font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">

                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $entry['from_color'] }}"></span>

                                                {{ $entry['from_label'] }}

                                            </span>

                                            <span class="text-bgray-600 dark:text-bgray-300">

                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h9.586L11.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 1.414L13.586 11H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                                </svg>

                                            </span>

                                            <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">

                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $entry['to_color'] }}"></span>

                                                {{ $entry['to_label'] }}

                                            </span>

                                        </div>

                                        <span class="text-xs text-bgray-700 dark:text-bgray-300">
                                            {{ $entry['changed_by'] }}
                                            at
                                            @appDateTime($entry['changed_at'])
                                        </span>

                                    </div>

                                    @if (filled($entry['remarks']))
                                        <p class="mt-3 text-sm leading-6 text-bgray-700 dark:text-bgray-300">
                                            {{ $entry['remarks'] }}
                                        </p>
                                    @endif

                                </div>

                            </div>
                        @endforeach

                    </div>

                @endif

            </div>

        </section>


        {{-- Stage Timeline --}}
        <section class="overflow-hidden rounded-[8px] border border-bgray-200 bg-white shadow-sm dark:border-darkblack-400 dark:bg-darkblack-600">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bgray-200 bg-bgray-50/80 px-5 py-4 dark:border-darkblack-400 dark:bg-darkblack-500/60">

                <div>
                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">
                        Stage Timeline
                    </h4>
                </div>

                <div class="flex flex-wrap items-center gap-2">

                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300">

                        <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $currentStage['color'] }}"></span>

                        {{ $currentStage['label'] }}

                    </span>

                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-bgray-700 dark:bg-darkblack-600 dark:text-bgray-300">

                        {{ $stageHistory->count() }}

                        {{ \Illuminate\Support\Str::plural('change', $stageHistory->count()) }}

                    </span>

                </div>

            </div>


            <div class="max-h-[560px] min-h-[320px] overflow-y-auto p-5">

                @if ($stageHistory->isEmpty())

                    <div class="rounded-xl border border-dashed border-bgray-300 px-4 py-8 text-center text-sm text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">
                        No stage changes recorded yet.
                    </div>
                @else
                    <div class="space-y-0">

                        @foreach ($stageHistory as $entry)
                            <div class="relative flex gap-4 pb-6 last:pb-0">

                                @if (!$loop->last)
                                    <span class="absolute left-[9px] top-6 h-full w-px bg-bgray-200 dark:bg-darkblack-400"></span>
                                @endif

                                <span class="relative z-10 mt-1 inline-flex h-5 w-5 shrink-0 rounded-full border-4 border-white dark:border-darkblack-600" style="background-color: {{ $entry['to_color'] }}"></span>

                                <div class="min-w-0 flex-1 rounded-xl border border-bgray-200 p-4 dark:border-darkblack-400">

                                    <div class="flex flex-wrap items-center justify-between gap-3">

                                        <div class="flex min-w-0 flex-wrap items-center gap-2">

                                            <span class="inline-flex items-center gap-2 rounded-full bg-bgray-50 px-3 py-1 text-xs font-semibold text-bgray-600 dark:bg-darkblack-500 dark:text-bgray-300">

                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $entry['from_color'] }}"></span>

                                                {{ $entry['from_label'] }}

                                            </span>

                                            <span class="text-bgray-600 dark:text-bgray-300">

                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h9.586L11.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 1.414L13.586 11H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                                </svg>

                                            </span>

                                            <span class="inline-flex items-center gap-2 rounded-full border border-bgray-200 px-3 py-1 text-xs font-semibold text-bgray-700 dark:border-darkblack-400 dark:text-bgray-300">

                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $entry['to_color'] }}"></span>

                                                {{ $entry['to_label'] }}

                                            </span>

                                        </div>

                                        <span class="text-xs text-bgray-700 dark:text-bgray-300">
                                            {{ $entry['changed_by'] }}
                                            at
                                            @appDateTime($entry['changed_at'])
                                        </span>

                                    </div>

                                    @if (filled($entry['remarks']))
                                        <p class="mt-3 text-sm leading-6 text-bgray-700 dark:text-bgray-300">
                                            {{ $entry['remarks'] }}
                                        </p>
                                    @endif

                                </div>

                            </div>
                        @endforeach

                    </div>

                @endif

            </div>

        </section>

    </div>

    @can('project_tracking.create')
        {{-- =========================================================
        Project Tracking Create Modal
    ========================================================== --}}
        @include('projects.partials.project-trackings.form', [
            'project' => $project,
        ])
    @endcan

    {{-- Project Tracking View Modal --}}
    <div class="modal fixed inset-0 z-[80] hidden items-center justify-center overflow-y-auto" data-project-tracking-view-modal>
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-project-tracking-view-modal-close></div>

        <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
            <div class="relative z-10 w-full max-w-3xl">
                <div class="overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600">

                    {{-- Header --}}
                    <div class="flex items-center justify-between gap-4 border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <div>
                            <h4 class="text-xl font-semibold text-bgray-900 dark:text-white">
                                Project Tracking
                            </h4>

                            <p class="mt-1 text-sm text-bgray-700 dark:text-bgray-300">
                                Tracking details
                            </p>
                        </div>

                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-bgray-500 transition hover:bg-bgray-100 hover:text-bgray-900 dark:hover:bg-darkblack-500 dark:hover:text-white" data-project-tracking-view-modal-close aria-label="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Content --}}
                    <div class="max-h-[80vh] overflow-y-auto px-6 py-6 sm:px-7" data-project-tracking-view-content>
                        <div class="flex items-center justify-center py-12">
                            <span class="text-sm text-bgray-500 dark:text-bgray-400">
                                Loading...
                            </span>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex justify-end border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400 sm:px-7">
                        <button type="button" class="rounded-lg border border-bgray-300 bg-white px-5 py-2.5 text-sm font-medium text-bgray-700 transition hover:bg-bgray-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:bg-darkblack-400" data-project-tracking-view-modal-close>
                            Close
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

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
