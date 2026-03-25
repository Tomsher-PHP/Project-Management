<div class="mb-6 rounded-lg bg-white p-5 dark:bg-darkblack-600">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        <!-- LEFT: Project Info -->
        <div>
            <div class="flex items-center gap-3">
                <!-- Priority Indicator -->
                <div class="h-10 w-1 rounded {{ $priority['bg_class'] ?? 'bg-gray-300' }}"></div>

                <div>
                    <h2 class="text-xl font-bold text-bgray-900 dark:text-white" id="project-name-display">
                        {{ $project->name }}
                    </h2>
                    <p class="text-sm text-bgray-500">
                        Code: {{ $project->project_code ?? '--' }}
                    </p>
                </div>
            </div>

            <!-- Meta Info -->
            <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-bgray-600 dark:text-bgray-300">

                <span>
                    <strong>Customer:</strong> {{ $project->customer->name ?? '--' }}
                </span>

                <span>
                    <strong>Project Type:</strong> {{ strtoupper($project->project_type ?? '--') }}
                </span>

                <span>
                    <strong>Start:</strong> {{ optional($project->start_date)->format(config('constants.date_format')) ?? '--' }}
                </span>

                <span>
                    <strong>Internal End:</strong> {{ optional($project->internal_end_date)->format(config('constants.date_format')) ?? '--' }}
                </span>

                <span>
                    <strong>Customer End:</strong> {{ optional($project->client_end_date)->format(config('constants.date_format')) ?? '--' }}
                </span>

            </div>
        </div>

        <!-- RIGHT: Status + Priority -->
        <div class="flex items-center gap-3">

            <!-- Status -->
            <span class="px-4 py-1.5 rounded-full text-sm font-semibold {{ $project->status ? 'bg-success-50 text-success-500' : 'bg-gray-100 text-gray-500' }}">
                {{ $project->projectStatus->name ?? 'No Status' }}
            </span>

            <!-- Project Priority -->
            <span class="px-4 py-1.5 rounded-full text-sm font-semibold {{ $priority['bg_class'] ?? 'bg-gray-100 text-gray-500' }} {{ $priority['bg_text'] ?? 'text-gray-500' }}">
                {{ $priority['label'] ?? '--' }}
            </span>

        </div>

    </div>
</div>
