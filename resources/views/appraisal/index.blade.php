@extends('layouts.master')

@section('page-content')
    @php
        $canAssignAppraisals = auth()->user()?->can('appraisal.create');
    @endphp

    <div class="space-y-1" data-appraisal-root data-auth-user-id="{{ auth()->id() }}" data-assignment-url="{{ route('appraisal.assignment-data') }}" data-submit-url="{{ route('appraisal.assign') }}" data-reviewer-submit-url="{{ route('appraisal.assign-reviewers') }}" data-publish-url="{{ route('appraisal.publish') }}" data-show-url-template="{{ route('appraisal.show', ['appraisal' => '__ID__']) }}" data-unpublish-url-template="{{ route('appraisal.unpublish', ['appraisal' => '__ID__']) }}" data-agree-kpi-url-template="{{ route('appraisal.agree-kpi', ['appraisal' => '__ID__']) }}"
        data-answer-page-url-template="{{ route('appraisal.answer', ['appraisal' => '__ID__']) }}" data-can-assign="{{ $canAssignAppraisals ? 'true' : 'false' }}">
        <script type="application/json" data-appraisal-initial-data>
            @json($assignmentData)
        </script>

        <div class="flex flex-wrap items-center gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="inline-flex items-center rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-200" data-appraisal-tab-button data-tab="my">
                    Appraisals
                </button>
                @if ($canAssignAppraisals)
                    <button type="button" class="inline-flex items-center rounded-lg border border-bgray-200 bg-bgray-50 px-4 py-2 text-sm font-semibold text-bgray-700 transition duration-200 hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-tab-button data-tab="assign">
                        Assign
                    </button>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-filters.button />

                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Period
                    </span>

                    <select class="tom-select-no-search min-w-[150px]" data-appraisal-month>
                        @foreach ($months as $monthValue => $monthLabel)
                            <option value="{{ $monthValue }}" @selected((int) $monthValue === (int) $month)>
                                {{ $monthLabel }}
                            </option>
                        @endforeach
                    </select>

                    <select class="tom-select-no-search min-w-[120px]" data-appraisal-year>
                        @foreach ($years as $yearValue => $yearLabel)
                            <option value="{{ $yearValue }}" @selected((int) $yearValue === (int) $year)>
                                {{ $yearLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <section class="space-y-3" data-appraisal-tab-panel="my">
            <div class="custom-scroll flex items-center gap-3 overflow-x-auto py-0 dark:bg-darkblack-700" data-appraisal-summary-section>
                @foreach ($myAppraisalSummary as $tile)
                    <div class="group relative flex min-w-[160px] flex-1 shrink-0 items-center rounded-xl border border-bgray-300 bg-white p-3 transition-all duration-300 hover:border-[#d8e4f6] hover:shadow-[0_4px_12px_-4px_rgba(0,0,0,0.05)] dark:border-darkblack-400 dark:bg-darkblack-600">
                        <div class="min-w-0 flex-1">
                            <h4 class="truncate text-[10px] font-bold uppercase tracking-wider text-bgray-700 dark:text-bgray-300">{{ $tile['label'] }}</h4>
                            <p class="text-xl font-black leading-none {{ $tile['accent'] }}">{{ $tile['count'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-lg bg-white px-6 py-5 shadow-sm dark:bg-darkblack-600">
                <div class="table-content w-full overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                <th class="px-4 py-4 text-left xl:w-[300px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">User</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[220px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">KPI</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[150px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Questions / Categories</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[150px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Current Stage</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[130px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[200px] xl:pl-0 xl:pr-6">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Average Ratings</span>
                                </th>
                                <th class="px-4 py-4 text-left xl:w-[100px] xl:px-0">
                                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Action</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody data-appraisal-my-list></tbody>
                    </table>
                </div>
                <x-pagination :paginator="$myAppraisalsPaginator" :per-page="$perPage" />
            </div>
        </section>

        @if ($canAssignAppraisals)
            <section class="hidden" data-appraisal-tab-panel="assign">
                <div class="rounded-lg bg-white px-5 py-5 shadow-sm dark:bg-darkblack-600">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-bgray-900 dark:text-white">Assign Appraisals</h3>
                            <p class="text-sm text-bgray-600 dark:text-bgray-300"><span data-appraisal-selected-count>0</span> selected</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-appraisal-open-assign disabled>
                                Assign
                            </button>
                            <button type="button" class="rounded-lg border border-success-200 bg-success-50 px-4 py-2 text-sm font-semibold text-success-400 transition hover:border-success-300 disabled:cursor-not-allowed disabled:opacity-50 dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-300" data-appraisal-publish-selected disabled>
                                Publish Selected
                            </button>
                        </div>
                    </div>

                    <div class="table-content mt-5 w-full overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                    <th class="w-12 px-4 py-4 text-left">
                                        <input type="checkbox" class="h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-users-select-all>
                                    </th>
                                    <th class="px-4 py-4 text-left xl:w-[250px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">User</span>
                                    </th>
                                    <th class="px-4 py-4 text-left xl:w-[200px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">KPI</span>
                                    </th>
                                    <th class="px-4 py-4 text-left xl:w-[220px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Categories</span>
                                    </th>
                                    <th class="px-4 py-4 text-center xl:w-[100px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Questions</span>
                                    </th>
                                    <th class="px-4 py-4 text-left xl:w-[180px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Reviewers</span>
                                    </th>
                                    <th class="px-4 py-4 text-left xl:w-[120px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span>
                                    </th>
                                    <th class="px-4 py-4 text-left xl:w-[200px] xl:px-0">
                                        <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Action</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody data-appraisal-users></tbody>
                        </table>
                    </div>
                    <div data-appraisal-assign-pagination>
                        @if ($usersPaginator)
                            <x-pagination :paginator="$usersPaginator" :per-page="$perPage" />
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if ($canAssignAppraisals)
            <div class="modal fixed inset-0 z-[90] hidden items-center justify-center overflow-y-auto" data-appraisal-assign-modal>
                <div class="fixed inset-0 bg-black/40 dark:bg-black/60"></div>

                <div class="relative z-10 max-h-[92vh] w-full max-w-7xl overflow-hidden rounded-lg bg-white shadow-xl dark:bg-darkblack-600" data-appraisal-modal-panel>
                    <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400" data-appraisal-modal-header>
                        <div>
                            <h3 class="text-xl font-bold text-bgray-900 dark:text-white" data-appraisal-modal-title>Assign Appraisal</h3>
                            <p class="mt-1 hidden text-sm font-medium text-bgray-600 dark:text-bgray-300" data-appraisal-modal-subtitle></p>
                        </div>
                        <button type="button" class="text-2xl leading-none text-bgray-600 hover:text-bgray-900 dark:text-bgray-300 dark:hover:text-white" data-appraisal-modal-close aria-label="Close">×</button>
                    </div>

                    <div class="max-h-[calc(92vh-145px)] overflow-y-auto px-6 py-5" data-appraisal-assignment-step="1">
                        <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500" data-appraisal-selected-users-summary>
                            <p class="text-sm font-semibold text-bgray-900 dark:text-white" data-appraisal-modal-selected-count>0 Users Selected</p>
                            <div class="mt-3 flex flex-wrap gap-2" data-appraisal-modal-selected-users></div>
                        </div>

                        <div class="mt-5">
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">KPI <x-red-star /></label>
                            <select class="w-full" data-appraisal-kpi-select>
                                <option value="">Select KPI</option>
                            </select>
                        </div>

                        <div class="mt-4">
                            <label class="mb-2 block text-sm font-medium text-bgray-700 dark:text-bgray-50">KPI Description</label>
                            <div class="custom-quill">
                                <div class="h-48 min-h-[110px] rounded-b-lg bg-white dark:bg-darkblack-500" data-appraisal-kpi-description></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 mt-6">
                            <!-- Left Panel (larger) -->
                            <div class="lg:col-span-3">
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                    <h4 class="text-base font-bold text-bgray-900 dark:text-white">Categories & Questions</h4>
                                </div>

                                <div class="space-y-4 max-h-[380px] lg:max-h-[420px] overflow-y-auto pr-2" data-appraisal-modal-categories></div>
                                <button type="button" class="mt-4 rounded-lg border border-success-200 bg-success-50 px-4 py-2 text-sm font-semibold text-success-400 transition hover:border-success-300 disabled:cursor-not-allowed disabled:opacity-50 dark:border-success-900/40 dark:bg-darkblack-500 dark:text-success-300" data-appraisal-assignment-category-add>
                                    + Add Category
                                </button>
                            </div>

                            <!-- Right Panel (smaller) -->
                            <aside class="lg:col-span-1 rounded-xl border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                                <div class="mb-4 -mx-4 -mt-4 rounded-t-xl bg-bgray-100 px-4 py-3 dark:bg-darkblack-600 border-b border-bgray-200 dark:border-darkblack-400">
                                    <h4 class="text-sm font-bold uppercase tracking-wider text-bgray-600 dark:text-bgray-300">Category Templates</h4>
                                </div>
                                <div class="space-y-3 max-h-[300px] lg:max-h-[340px] overflow-y-auto pr-1" data-appraisal-assign-templates-list>
                                    <!-- Templates list rendered dynamically via JS -->
                                </div>
                            </aside>
                        </div>
                    </div>

                    <div class="hidden max-h-[calc(92vh-145px)] overflow-y-auto px-6 py-5" data-appraisal-assignment-step="2">
                        <div class="mb-4">
                            <h4 class="text-lg font-bold text-bgray-900 dark:text-white">Reviewer Assignment</h4>
                            <p class="mt-1 text-sm font-medium text-bgray-600 dark:text-bgray-300">Configure the reporting hierarchy for each employee.</p>
                        </div>
                        <div class="space-y-4" data-appraisal-reviewer-assignments></div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400" data-appraisal-assignment-footer="1">
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-modal-close>Cancel</button>
                        <button type="button" class="hidden rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400" data-appraisal-reviewers-next>Reviewers</button>
                        <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-appraisal-assignment-continue>Continue</button>
                    </div>

                    <div class="hidden flex-wrap items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400" data-appraisal-assignment-footer="2">
                        <button type="button" class="mr-auto rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-assignment-back>Back</button>
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-modal-close>Cancel</button>
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 disabled:cursor-not-allowed disabled:opacity-50 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-reviewers-submit="draft">Assign</button>
                        <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-appraisal-reviewers-submit="published">Assign & Publish</button>
                    </div>
                </div>
            </div>
        @endif

        <div class="modal fixed inset-0 z-[90] hidden items-center justify-center overflow-y-auto" data-appraisal-kpi-agreement-modal>
            <div class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70" data-appraisal-kpi-agreement-close></div>

            <div class="relative flex min-h-full w-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 w-full max-w-3xl overflow-hidden rounded-lg bg-white shadow-xl dark:bg-darkblack-600">
                    <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                        <h3 class="text-xl font-bold text-bgray-900 dark:text-white">KPI Agreement</h3>
                        <button type="button" class="text-2xl leading-none text-bgray-600 hover:text-bgray-900 dark:text-bgray-300 dark:hover:text-white" data-appraisal-kpi-agreement-close aria-label="Close">×</button>
                    </div>

                    <div class="max-h-[calc(85vh-145px)] overflow-y-auto px-6 py-5">
                        <div class="rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <p class="text-xs font-bold uppercase tracking-[0.08em] text-bgray-600 dark:text-bgray-300">KPI Title</p>
                            <h4 class="mt-2 text-lg font-bold text-bgray-900 dark:text-white" data-appraisal-kpi-agreement-title></h4>
                        </div>

                        <div class="mt-4 rounded-lg border border-bgray-200 bg-white p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <p class="text-xs font-bold uppercase tracking-[0.08em] text-bgray-600 dark:text-bgray-300">KPI Description</p>
                            <div class="prose prose-sm mt-3 max-w-none text-bgray-700 dark:prose-invert dark:text-bgray-100" data-appraisal-kpi-agreement-description></div>
                        </div>

                        <label class="mt-5 flex items-start gap-3 rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">
                            <input type="checkbox" class="mt-1 h-4 w-4 rounded border-bgray-300 text-success-300 focus:ring-success-300 dark:border-darkblack-400 dark:bg-darkblack-600" data-appraisal-kpi-agreement-checkbox>
                            <span class="text-sm font-medium text-bgray-700 dark:text-bgray-100">I have read, understood and agree to the KPI and expectations for this appraisal.</span>
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                        <button type="button" class="rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-semibold text-bgray-700 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-50" data-appraisal-kpi-agreement-close>Cancel</button>
                        <button type="button" class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50" data-appraisal-kpi-agreement-submit disabled>Agree & Continue</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
    @php
        $selectedUserFilterIds = collect(['user_id', 'staff_id', 'users'])
            ->flatMap(function (string $key) {
                $value = request($key, []);
                return is_array($value) ? $value : [$value];
            })
            ->filter(fn($value) => filled($value))
            ->map(fn($value) => (int) $value)
            ->filter(fn(int $value) => $value > 0)
            ->unique()
            ->values();

        $isUserFilterApplied = $selectedUserFilterIds->isNotEmpty();

        $filterDependencies = [
            'teams' => $teams
                ->map(
                    fn($team) => [
                        'id' => $team->id,
                        'name' => $team->name,
                        'users' => $team->users
                            ->map(
                                fn($user) => [
                                    'id' => $user->id,
                                    'name' => $user->name,
                                ],
                            )
                            ->values(),
                    ],
                )
                ->values(),
            'hasExplicitUserFilter' => $isUserFilterApplied,
            'hasUserFilterAppliedParameter' => request()->has('user_filter_applied'),
        ];
    @endphp

    <x-filters.drawer>
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">

        <x-filters.multi-select name="teams" label="Teams" :options="$teams" id="appraisal-team-filter" />
        <input type="hidden" name="user_filter_applied" id="appraisal-user-filter-applied" value="{{ $isUserFilterApplied ? '1' : '0' }}">
        <x-filters.multi-select name="user_id" label="Users" :options="$users" id="appraisal-user-filter" />
        <x-filters.multi-select name="department_id" label="Departments" :options="$departments" />

        <div data-filter-tab="my">
            <div class="space-y-5">
                <x-filters.select name="kpi" label="KPI" :options="$kpiOptions" />
                <x-filters.multi-select name="my_status" label="Status" :options="$myStatusOptions" />
            </div>
        </div>

        <div data-filter-tab="assign">
            <x-filters.select name="status" label="Status" :options="$statusOptions" />
        </div>
    </x-filters.drawer>

    <script id="appraisal-filter-dependencies" type="application/json">
        @json($filterDependencies)
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dependenciesNode = document.getElementById('appraisal-filter-dependencies');
            const teamSelect = document.getElementById('appraisal-team-filter');
            const userSelect = document.getElementById('appraisal-user-filter');
            const userFilterAppliedInput = document.getElementById('appraisal-user-filter-applied');

            if (!dependenciesNode || !teamSelect || !userSelect) {
                return;
            }

            let dependencies = {
                teams: []
            };

            try {
                dependencies = JSON.parse(dependenciesNode.textContent || '{}');
            } catch (error) {
                return;
            }

            const teams = Array.isArray(dependencies.teams) ? dependencies.teams : [];
            const hasExplicitUserFilter = dependencies.hasExplicitUserFilter === true;
            const hasUserFilterAppliedParameter = dependencies.hasUserFilterAppliedParameter === true;
            let isSyncingUsersFromTeams = false;

            const normalizeValues = (value) => {
                if (Array.isArray(value)) {
                    return value.map((item) => String(item)).filter(Boolean);
                }

                if (value === null || value === undefined || value === '') {
                    return [];
                }

                return [String(value)];
            };

            const getSelectedValues = (select) => {
                if (select.tomselect) {
                    return normalizeValues(select.tomselect.getValue());
                }

                return Array.from(select.selectedOptions)
                    .map((option) => String(option.value))
                    .filter(Boolean);
            };

            const setSelectedValues = (select, values) => {
                const normalizedValues = normalizeValues(values);

                if (select.tomselect) {
                    normalizedValues.forEach((value) => {
                        if (!select.tomselect.options[value]) {
                            const option = Array.from(select.options).find((item) => item.value === value);

                            if (option) {
                                select.tomselect.addOption({
                                    value,
                                    text: option.textContent
                                });
                            }
                        }
                    });

                    select.tomselect.setValue(normalizedValues, true);
                    select.tomselect.refreshItems();
                    select.tomselect.refreshOptions(false);
                }

                Array.from(select.options).forEach((option) => {
                    option.selected = normalizedValues.includes(String(option.value));
                });

                select.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            };

            const syncUserFilterApplied = () => {
                if (userFilterAppliedInput) {
                    userFilterAppliedInput.value = getSelectedValues(userSelect).length ? '1' : '0';
                }
            };

            const ensureUserOptions = (users) => {
                const existingOptions = new Set(Array.from(userSelect.options).map((option) => String(option.value)));

                users.forEach((user) => {
                    const value = String(user.id);

                    if (!existingOptions.has(value)) {
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = user.name;
                        userSelect.appendChild(option);
                        existingOptions.add(value);
                    }

                    if (userSelect.tomselect && !userSelect.tomselect.options[value]) {
                        userSelect.tomselect.addOption({
                            value,
                            text: user.name
                        });
                    }
                });

                userSelect.tomselect?.refreshOptions(false);
            };

            let previousTeamIds = getSelectedValues(teamSelect);
            let savedUserIds = previousTeamIds.length ? [] : getSelectedValues(userSelect);

            const syncUsersFromTeams = () => {
                const selectedTeamIds = getSelectedValues(teamSelect);

                if (!previousTeamIds.length && selectedTeamIds.length) {
                    savedUserIds = getSelectedValues(userSelect);
                }

                if (!selectedTeamIds.length) {
                    isSyncingUsersFromTeams = true;
                    setSelectedValues(userSelect, savedUserIds);
                    isSyncingUsersFromTeams = false;
                    savedUserIds = getSelectedValues(userSelect);
                    previousTeamIds = selectedTeamIds;
                    syncUserFilterApplied();
                    return;
                }

                const usersById = new Map();

                teams
                    .filter((team) => selectedTeamIds.includes(String(team.id)))
                    .forEach((team) => {
                        (Array.isArray(team.users) ? team.users : []).forEach((user) => {
                            usersById.set(String(user.id), user);
                        });
                    });

                const users = Array.from(usersById.values()).sort((first, second) => {
                    return String(first.name).localeCompare(String(second.name));
                });

                ensureUserOptions(users);
                isSyncingUsersFromTeams = true;
                setSelectedValues(userSelect, users.map((user) => String(user.id)));
                isSyncingUsersFromTeams = false;
                previousTeamIds = selectedTeamIds;
                syncUserFilterApplied();
            };

            teamSelect.addEventListener('change', syncUsersFromTeams);
            userSelect.addEventListener('change', () => {
                if (!isSyncingUsersFromTeams) {
                    savedUserIds = getSelectedValues(userSelect);
                    syncUserFilterApplied();
                }
            });

            const initializeTeamUserSync = () => {
                if (!hasExplicitUserFilter && !hasUserFilterAppliedParameter && getSelectedValues(teamSelect).length) {
                    syncUsersFromTeams();
                }
            };

            document.addEventListener('tomselect:ready', initializeTeamUserSync, {
                once: true
            });
            window.requestAnimationFrame(initializeTeamUserSync);
            window.requestAnimationFrame(syncUserFilterApplied);
        });
    </script>
@endsection

@push('scripts')
    @vite('resources/js/modules/appraisal/appraisal.js')
@endpush
