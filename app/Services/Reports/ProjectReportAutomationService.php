<?php

namespace App\Services\Reports;

use App\Jobs\SendProjectReportDelivery;
use App\Models\DailyWorkReportDelivery;
use App\Models\DailyWorkReportLog;
use App\Models\DailyWorkReportRecipient;
use App\Models\DailyWorkReportSetting;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectStatus;
use App\Models\ProjectTimeline;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectReportAutomationService
{
    public function __construct(
        protected ProjectReportService $projectReportService
    ) {}

    /**
     * Prepare complete structured report data for a Project Report configuration.
     *
     * @param DailyWorkReportSetting|int $setting
     * @param Carbon|null $referenceDate
     * @param User|null $user
     * @param bool|null $allowCustomerColumns
     * @return array
     */
    public function prepareReportData(DailyWorkReportSetting|int $setting, ?Carbon $referenceDate = null, ?User $user = null, ?bool $allowCustomerColumns = null): array
    {
        $settingModel = $setting instanceof DailyWorkReportSetting
            ? $setting
            : DailyWorkReportSetting::query()
            ->where('report_key', 'project_report')
            ->where('id', $setting)
            ->firstOrFail();

        $timezone = $settingModel->timezone ?: config('constants.timezone', config('app.timezone'));
        $now = ($referenceDate ? $referenceDate->copy() : Carbon::now($timezone))->setTimezone($timezone);

        $reportingPeriod = (string) ($settingModel->reporting_period ?: 'all_time');
        [$periodStart, $periodEnd, $periodLabel] = $this->calculatePeriodBounds($reportingPeriod, $timezone, $now);

        $dateFilter = (string) ($settingModel->settings['filters']['date_filter']
            ?? $settingModel->settings['date_filter']
            ?? 'status_changed_date');

        $dateFilterLabel = config("report-settings.project_report.date_filters.{$dateFilter}")
            ?? match ($dateFilter) {
                'status_change_date' => config('report-settings.project_report.date_filters.status_changed_date', 'Status Changed Date within Period'),
                'start_date' => 'Project Start Date within Period',
                'end_date' => 'Project End Date within Period',
                'created_at' => 'Project Created Date within Period',
                default => config('report-settings.project_report.date_filters.status_changed_date', 'Status Changed Date within Period'),
            };

        $statusIds = $settingModel->settings['filters']['project_status_ids']
            ?? $settingModel->settings['project_statuses']
            ?? $settingModel->settings['project_status_ids']
            ?? [];

        $selectedStatusNames = !empty($statusIds)
            ? ProjectStatus::whereIn('id', (array) $statusIds)->pluck('name')->all()
            : [];
        $selectedStatusesText = !empty($selectedStatusNames)
            ? implode(', ', $selectedStatusNames)
            : 'All';

        $canViewCustomer = $this->canAccessCustomerColumns($user, $allowCustomerColumns);
        $resolvedColumns = $this->resolveColumns($settingModel, $canViewCustomer);
        $columnKeys = array_keys($resolvedColumns);

        $projects = $this->getProjectsForConfiguration(
            $settingModel,
            $periodStart,
            $periodEnd,
            $user
        );

        $projectRows = [];
        $flatRows = [];
        $totalTimelinesCount = 0;
        $multipleTimelinesCount = 0;

        foreach ($projects as $project) {
            /** @var Collection $timelines */
            $timelines = $project->reportTimelines ?? collect();
            $timelineCount = $timelines->count();
            $totalTimelinesCount += $timelineCount;

            $hasMultipleTimelines = $timelineCount > 1;
            if ($hasMultipleTimelines) {
                $multipleTimelinesCount++;
            }

            $primaryTimeline = $project->primaryReportTimeline;

            $primaryValues = [];
            foreach ($columnKeys as $key) {
                $primaryValues[$key] = $this->resolveColumnValue($project, $primaryTimeline, $key);
            }

            $timelineList = [];
            foreach ($timelines as $index => $timeline) {
                $tValues = [];
                foreach ($columnKeys as $key) {
                    $tValues[$key] = $this->resolveColumnValue($project, $timeline, $key);
                }

                $timelineList[] = [
                    'id' => $timeline->id,
                    'name' => $timeline->name,
                    'status' => $timeline->report_status_label,
                    'is_active' => $timeline->status === ProjectTimeline::STATUS_ACTIVE,
                    'start_date' => AppServiceProvider::formatAppDate($timeline->start_date, '-'),
                    'end_date' => AppServiceProvider::formatAppDate($timeline->end_date, '-'),
                    'customer_end_date' => AppServiceProvider::formatAppDate($timeline->customer_end_date, '-'),
                    'estimated_hours' => formatSecondsToHoursMinutes((int) $timeline->report_estimated_seconds),
                    'customer_estimated_hours' => formatSecondsToHoursMinutes((int) $timeline->report_customer_estimated_seconds),
                    'actual_hours' => formatSecondsToHoursMinutes((int) $timeline->report_actual_seconds),
                    'progress' => ($timeline->report_progress_percentage ?? 0) . '%',
                    'customer_progress' => ($timeline->report_customer_progress_percentage ?? 0) . '%',
                    'values' => $tValues,
                ];

                $flatRows[] = [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'timeline_id' => $timeline->id,
                    'timeline_name' => $timeline->name,
                    'is_primary' => $index === 0,
                    'has_multiple_timelines' => $hasMultipleTimelines,
                    'timeline_index' => $index,
                    'values' => $tValues,
                ];
            }

            if ($timelineCount === 0) {
                $flatRows[] = [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'timeline_id' => null,
                    'timeline_name' => '-',
                    'is_primary' => true,
                    'has_multiple_timelines' => false,
                    'timeline_index' => 0,
                    'values' => $primaryValues,
                ];
            }

            $projectRows[] = [
                'id' => $project->id,
                'code' => $project->project_code,
                'name' => $project->name,
                'customer' => $project->customer?->name ?? '-',
                'sales_person' => $project->salesPerson?->name ?? '-',
                'status' => $project->projectStatus?->name ?? '-',
                'stage' => $project->projectStage?->name ?? '-',
                'priority' => config('project_constants.project_priorities.' . $project->priority . '.label')
                    ?? ucfirst(str_replace('_', ' ', $project->priority ?? '-')),
                'milestone_status' => sprintf(
                    '%d / %d',
                    (int) ($project->completed_milestones ?? 0),
                    (int) ($project->total_milestones ?? 0)
                ),
                'has_multiple_timelines' => $hasMultipleTimelines,
                'eligible_timelines_count' => $timelineCount,
                'primary_values' => $primaryValues,
                'timelines' => $timelineList,
            ];
        }

        return [
            'metadata' => [
                'setting_id' => $settingModel->id,
                'setting_key' => $settingModel->setting_key,
                'report_name' => $settingModel->report_name,
                'frequency' => $settingModel->frequency,
                'timezone' => $timezone,
                'reporting_period' => $reportingPeriod,
                'reporting_period_label' => $periodLabel,
                'date_filter' => $dateFilter,
                'date_filter_label' => $dateFilterLabel,
                'selected_status_ids' => array_values(array_map('intval', (array) $statusIds)),
                'selected_statuses' => $selectedStatusNames,
                'selected_statuses_text' => $selectedStatusesText,
                'period_start' => $periodStart?->toDateTimeString(),
                'period_end' => $periodEnd?->toDateTimeString(),
                'generated_at' => $now->toDateTimeString(),
                'generated_at_display' => $now->format('d M Y, h:i A (T)'),
                'total_projects' => count($projectRows),
                'total_rows' => count($flatRows),
                'is_empty' => empty($projectRows),
                'has_customer_columns' => $canViewCustomer,
            ],
            'columns' => $resolvedColumns,
            'headers' => array_values($resolvedColumns),
            'column_keys' => $columnKeys,
            'projects' => $projectRows,
            'flat_rows' => $flatRows,
            'stats' => [
                'total_projects' => count($projectRows),
                'total_timelines' => $totalTimelinesCount,
                'projects_with_multiple_timelines' => $multipleTimelinesCount,
            ],
        ];
    }

    /**
     * Retrieve projects according to the configuration filters and access policy.
     */
    public function getProjectsForConfiguration(DailyWorkReportSetting $setting, ?Carbon $periodStart = null, ?Carbon $periodEnd = null, ?User $user = null): Collection
    {
        $query = $this->baseProjectQuery($user);

        // 1. Status Filter
        $statusIds = $setting->settings['filters']['project_status_ids']
            ?? $setting->settings['project_statuses']
            ?? $setting->settings['project_status_ids']
            ?? [];

        if (! empty($statusIds)) {
            $statusIds = array_values(array_map('intval', (array) $statusIds));
            $query->whereIn('projects.status_id', $statusIds);
        }

        // 2. Date Filter
        $dateFilter = (string) ($setting->settings['filters']['date_filter']
            ?? $setting->settings['date_filter']
            ?? 'status_changed_date');

        $this->applyDateFilter($query, $dateFilter, $periodStart, $periodEnd, $statusIds);

        // 3. Eager Loading & Milestones count
        $query->with([
            'customer:id,name',
            'salesPerson:id,name',
            'projectStatus:id,name,color',
            'projectStage:id,name,color',
            'projectTimelines' => fn($tQuery) => $tQuery
                ->select([
                    'id',
                    'project_id',
                    'name',
                    'status',
                    'start_date',
                    'end_date',
                    'customer_end_date',
                    'estimated_time_seconds',
                    'customer_estimate_seconds',
                    'actual_time_seconds',
                    'sort_order',
                ])
                ->reorder()
                ->eligibleForProjectReport()
                ->orderForProjectReport(),
        ])
            ->withCount([
                'projectMilestones as total_milestones',
                'projectMilestones as completed_milestones' => function ($q) {
                    $q->where('status_id', ProjectMilestone::STATUS_COMPLETED);
                },
            ]);

        $query->orderBy('projects.name', 'asc')->orderByDesc('projects.id');

        $projects = $query->get();

        // 4. Reuse ProjectReportService to prepare calculated attributes
        return $this->projectReportService->prepareProjects($projects);
    }

    /**
     * Build base project query adhering to safe organization access policies.
     */
    protected function baseProjectQuery(?User $user = null): Builder
    {
        $query = Project::query();

        // Explicit safe reporting access policy: never include internal system/scratch projects
        $query->where('projects.is_system', false);

        // Scope to user if user context is provided or authenticated
        $effectiveUser = $user ?? auth()->user();

        if ($effectiveUser) {
            if (! ($effectiveUser->is_super_admin || $effectiveUser->can('project.view_all_projects'))) {
                $query->where(function ($q) use ($effectiveUser) {
                    $q->where('projects.added_by', $effectiveUser->id)
                        ->orWhereHas('members', function ($sub) use ($effectiveUser) {
                            $sub->where('users.id', $effectiveUser->id);
                        });
                });
            }
        }

        return $query;
    }

    /**
     * Apply the configured date filter to the query.
     */
    protected function applyDateFilter(Builder $query, string $dateFilter, ?Carbon $periodStart, ?Carbon $periodEnd, array $statusIds = []): void
    {
        if ($periodStart === null || $periodEnd === null) {
            return;
        }

        if ($dateFilter === 'status_changed_date' || $dateFilter === 'status_change_date') {
            $utcStart = $periodStart->copy()->utc()->toDateTimeString();
            $utcEnd = $periodEnd->copy()->utc()->toDateTimeString();

            $query->whereExists(function ($sub) use ($utcStart, $utcEnd, $statusIds) {
                $sub->select(DB::raw(1))
                    ->from('project_status_histories')
                    ->whereColumn('project_status_histories.project_id', 'projects.id')
                    ->whereBetween(
                        DB::raw('COALESCE(project_status_histories.added_at, project_status_histories.created_at)'),
                        [$utcStart, $utcEnd]
                    );

                if (! empty($statusIds)) {
                    $sub->whereIn('project_status_histories.status_id', $statusIds);
                }
            });
            return;
        }

        if ($dateFilter === 'created_at') {
            $query->whereBetween('projects.created_at', [
                $periodStart->copy()->utc()->toDateTimeString(),
                $periodEnd->copy()->utc()->toDateTimeString(),
            ]);
            return;
        }

        if ($dateFilter === 'start_date') {
            $startDateStr = $periodStart->toDateString();
            $endDateStr = $periodEnd->toDateString();
            $query->where(function (Builder $sub) use ($startDateStr, $endDateStr) {
                $sub->whereBetween('projects.start_date', [$startDateStr, $endDateStr])
                    ->orWhere(function (Builder $timelineSub) use ($startDateStr, $endDateStr) {
                        $timelineSub->whereNull('projects.start_date')
                            ->whereExists(function ($q) use ($startDateStr, $endDateStr) {
                                $q->select(DB::raw(1))
                                    ->from('project_timelines')
                                    ->whereColumn('project_timelines.project_id', 'projects.id')
                                    ->whereIn('project_timelines.status', [ProjectTimeline::STATUS_ACTIVE, ProjectTimeline::STATUS_COMPLETED])
                                    ->whereBetween('project_timelines.start_date', [$startDateStr, $endDateStr]);
                            });
                    });
            });
            return;
        }

        if ($dateFilter === 'end_date') {
            $startDateStr = $periodStart->toDateString();
            $endDateStr = $periodEnd->toDateString();
            $query->where(function (Builder $sub) use ($startDateStr, $endDateStr) {
                $sub->whereBetween('projects.end_date', [$startDateStr, $endDateStr])
                    ->orWhere(function (Builder $timelineSub) use ($startDateStr, $endDateStr) {
                        $timelineSub->whereNull('projects.end_date')
                            ->whereExists(function ($q) use ($startDateStr, $endDateStr) {
                                $q->select(DB::raw(1))
                                    ->from('project_timelines')
                                    ->whereColumn('project_timelines.project_id', 'projects.id')
                                    ->whereIn('project_timelines.status', [ProjectTimeline::STATUS_ACTIVE, ProjectTimeline::STATUS_COMPLETED])
                                    ->whereBetween('project_timelines.end_date', [$startDateStr, $endDateStr]);
                            });
                    });
            });
            return;
        }
    }

    /**
     * Calculate period boundaries in the configuration timezone.
     */
    public function calculatePeriodBounds(string $reportingPeriod, string $timezone, ?Carbon $referenceDate = null): array
    {
        $ref = ($referenceDate ? $referenceDate->copy() : Carbon::now($timezone))->setTimezone($timezone);

        switch ($reportingPeriod) {
            case 'previous_day':
                $start = $ref->copy()->subDay()->startOfDay();
                $end = $ref->copy()->subDay()->endOfDay();
                $label = 'Previous Day (' . $start->format('d M Y') . ')';
                break;

            case 'last_7_days':
                $start = $ref->copy()->subDays(7)->startOfDay();
                $end = $ref->copy()->endOfDay();
                $label = 'Last 7 Days (' . $start->format('d M Y') . ' - ' . $end->format('d M Y') . ')';
                break;

            case 'last_30_days':
                $start = $ref->copy()->subDays(30)->startOfDay();
                $end = $ref->copy()->endOfDay();
                $label = 'Last 30 Days (' . $start->format('d M Y') . ' - ' . $end->format('d M Y') . ')';
                break;

            case 'last_90_days':
                $start = $ref->copy()->subDays(90)->startOfDay();
                $end = $ref->copy()->endOfDay();
                $label = 'Last 90 Days (' . $start->format('d M Y') . ' - ' . $end->format('d M Y') . ')';
                break;

            case 'last_6_months':
                $start = $ref->copy()->subMonths(6)->startOfDay();
                $end = $ref->copy()->endOfDay();
                $label = 'Last 6 Months (' . $start->format('d M Y') . ' - ' . $end->format('d M Y') . ')';
                break;

            case 'last_1_year':
                $start = $ref->copy()->subYear()->startOfDay();
                $end = $ref->copy()->endOfDay();
                $label = 'Last 1 Year (' . $start->format('d M Y') . ' - ' . $end->format('d M Y') . ')';
                break;

            case 'all_time':
            default:
                $start = null;
                $end = null;
                $label = 'All Time';
                break;
        }

        return [$start, $end, $label];
    }

    /**
     * Resolve the allowed and configured column labels, preserving project_name.
     */
    public function resolveColumns(DailyWorkReportSetting $setting, bool $canViewCustomer = false): array
    {
        $availableColumnLabels = $this->projectReportService->getColumnLabels($canViewCustomer);

        $configured = $setting->settings['columns'] ?? [];
        if (is_string($configured)) {
            $configured = array_filter(explode(',', $configured));
        }
        $configured = (array) $configured;

        // Preserve mandatory Project Name column
        if (! in_array('project_name', $configured, true)) {
            array_unshift($configured, 'project_name');
        }

        $resolved = [];
        foreach ($configured as $columnKey) {
            if (isset($availableColumnLabels[$columnKey])) {
                $resolved[$columnKey] = $availableColumnLabels[$columnKey];
            }
        }

        if (empty($resolved)) {
            $resolved = [
                'project_name' => $availableColumnLabels['project_name'] ?? 'Project Name',
                'status' => $availableColumnLabels['status'] ?? 'Status',
            ];
        }

        return $resolved;
    }

    /**
     * Determine whether customer-sensitive columns can be viewed.
     */
    public function canAccessCustomerColumns(?User $user = null, ?bool $explicitAllow = null): bool
    {
        if ($explicitAllow !== null) {
            return $explicitAllow;
        }

        $effectiveUser = $user ?? auth()->user();

        if ($effectiveUser) {
            return $effectiveUser->can('project.customer_end_date');
        }

        // Safe default: Protect customer-sensitive columns during automated background execution
        return false;
    }

    /**
     * Resolve the rendered value of a specific column.
     */
    public function resolveColumnValue(Project $project, ?ProjectTimeline $timeline, string $column): string
    {
        $estimatedSeconds = (int) ($timeline?->report_estimated_seconds ?? $timeline?->estimated_time_seconds ?? 0);
        $customerEstimatedSeconds = (int) ($timeline?->report_customer_estimated_seconds ?? $timeline?->customer_estimate_seconds ?? 0);
        $actualSeconds = (int) ($timeline?->report_actual_seconds ?? $timeline?->actual_time_seconds ?? 0);

        $progress = $estimatedSeconds > 0
            ? round(($actualSeconds / $estimatedSeconds) * 100, 2)
            : ($timeline?->report_progress_percentage ?? 0);

        $customerProgress = $customerEstimatedSeconds > 0
            ? round(($actualSeconds / $customerEstimatedSeconds) * 100, 2)
            : ($timeline?->report_customer_progress_percentage ?? 0);

        return match ($column) {
            'project_name' => $project->name ?? '-',
            'customer' => $project->customer?->name ?? '-',
            'sales_person' => $project->salesPerson?->name ?? '-',
            'timeline' => $timeline?->name ?? '-',
            'timeline_status' => $timeline?->report_status_label
                ?? ($timeline ? ucfirst((string) config('project_constants.project_timeline_statuses.' . $timeline->status, 'Unknown')) : '-'),
            'start_date' => AppServiceProvider::formatAppDate($timeline?->start_date ?? $project->start_date, '-'),
            'end_date' => AppServiceProvider::formatAppDate($timeline?->end_date ?? $project->end_date, '-'),
            'customer_end_date' => AppServiceProvider::formatAppDate($timeline?->customer_end_date ?? $project->customer_end_date, '-'),
            'estimated_hours' => $timeline ? formatSecondsToHoursMinutes($estimatedSeconds) : '-',
            'customer_estimated_hours' => $timeline ? formatSecondsToHoursMinutes($customerEstimatedSeconds) : '-',
            'actual_hours' => $timeline ? formatSecondsToHoursMinutes($actualSeconds) : '-',
            'progress' => $timeline ? $progress . '%' : '-',
            'customer_progress' => $timeline ? $customerProgress . '%' : '-',
            'priority' => config('project_constants.project_priorities.' . $project->priority . '.label')
                ?? ucfirst(str_replace('_', ' ', $project->priority ?? '-')),
            'milestone_status' => sprintf(
                '%d / %d',
                (int) ($project->completed_milestones ?? 0),
                (int) ($project->total_milestones ?? 0)
            ),
            'status' => $project->projectStatus?->name ?? '-',
            'stage' => $project->projectStage?->name ?? '-',
            default => '-',
        };
    }

    /**
     * Resolve all active, effective internal and external recipients for a Project Report configuration.
     *
     * Ensures:
     * - Scoped strictly to report_key = 'project_report' and report_setting_id.
     * - Uses current email addresses from User model for active internal users.
     * - Excludes inactive or deleted internal users.
     * - Excludes invalid email formats.
     * - Respects effective_from and effective_until dates.
     * - Prevents duplicate recipient email addresses (internal users prioritized).
     */
    public function resolveRecipients(DailyWorkReportSetting|int $setting, ?string $effectiveDate = null): Collection
    {
        $settingId = $setting instanceof DailyWorkReportSetting ? $setting->id : (int) $setting;
        $effectiveDate ??= now()->toDateString();

        $recipientRecords = DailyWorkReportRecipient::query()
            ->where('report_key', 'project_report')
            ->where('report_setting_id', $settingId)
            ->effective($effectiveDate)
            ->with(['user' => fn($q) => $q->select(['id', 'name', 'email', 'is_active'])])
            ->get();

        $resolved = collect();
        $seenEmails = [];

        // Sort internal recipients first to prioritize their current email over external duplicates
        $sortedRecords = $recipientRecords->sortByDesc(fn($r) => $r->user_id ? 1 : 0);

        foreach ($sortedRecords as $recipient) {
            $email = null;
            $name = null;
            $type = 'external';
            $userId = null;

            if ($recipient->user_id) {
                // Internal recipient
                $user = $recipient->user;
                if (! $user || ! $user->is_active) {
                    continue; // Skip missing or inactive user
                }

                $email = mb_strtolower(trim((string) $user->email));
                $name = trim((string) ($user->name ?? $recipient->name ?? ''));
                $type = 'internal';
                $userId = $user->id;
            } else {
                // External recipient
                $email = mb_strtolower(trim((string) $recipient->email));
                $name = trim((string) ($recipient->name ?? ''));
            }

            // Validate email format
            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // Prevent duplicate recipient email addresses (internal prioritized)
            if (isset($seenEmails[$email])) {
                continue;
            }
            $seenEmails[$email] = true;

            $resolved->push([
                'email' => $email,
                'name' => $name ?: null,
                'type' => $type,
                'user_id' => $userId,
                'recipient_id' => $recipient->id,
                'sources' => [$type === 'internal' ? 'internal_user' : 'external_email'],
            ]);
        }

        return $resolved;
    }

    /**
     * Partition resolved recipients into normalized, deduplicated TO and CC recipient lists.
     *
     * Rules:
     * - Internal recipients -> TO (using current email from users table).
     * - External recipients -> CC (using configured email).
     * - Normalized (lowercase, trimmed) and deduplicated.
     * - An address must not appear in both TO and CC. Prioritize TO.
     * - If external recipients exist but no internal recipients, use config('mail.from.address') as TO, and keep external in CC.
     *
     * @param Collection $recipients
     * @return array{
     *     to: \Illuminate\Support\Collection,
     *     cc: \Illuminate\Support\Collection,
     *     to_emails: array<string>,
     *     cc_emails: array<string>,
     *     internal: \Illuminate\Support\Collection,
     *     external: \Illuminate\Support\Collection,
     *     fallback_to_used: bool,
     *     has_recipients: bool
     * }
     */
    public function partitionRecipients(Collection $recipients): array
    {
        $to = collect();
        $cc = collect();
        $seenTo = [];
        $seenCc = [];

        // 1. Process Internal recipients -> TO
        foreach ($recipients as $recipient) {
            if (($recipient['type'] ?? '') === 'internal') {
                $email = mb_strtolower(trim((string) ($recipient['email'] ?? '')));
                if (! empty($email) && ! isset($seenTo[$email])) {
                    $seenTo[$email] = true;
                    $to->push([
                        'email' => $email,
                        'name' => trim((string) ($recipient['name'] ?? '')) ?: null,
                        'type' => 'internal',
                        'user_id' => $recipient['user_id'] ?? null,
                    ]);
                }
            }
        }

        // 2. Process External recipients -> CC
        // Must exclude any address already in TO (Prioritize TO)
        foreach ($recipients as $recipient) {
            if (($recipient['type'] ?? '') === 'external') {
                $email = mb_strtolower(trim((string) ($recipient['email'] ?? '')));
                if (! empty($email) && ! isset($seenTo[$email]) && ! isset($seenCc[$email])) {
                    $seenCc[$email] = true;
                    $cc->push([
                        'email' => $email,
                        'name' => trim((string) ($recipient['name'] ?? '')) ?: null,
                        'type' => 'external',
                        'user_id' => null,
                    ]);
                }
            }
        }

        // 3. Fallback when external recipients exist but no internal recipients
        $fallbackToUsed = false;
        if ($to->isEmpty() && $cc->isNotEmpty()) {
            $fromAddress = mb_strtolower(trim((string) config('mail.from.address', 'noreply@example.com')));
            $fromName = config('mail.from.name', 'Project Management');

            $to->push([
                'email' => $fromAddress,
                'name' => $fromName,
                'type' => 'from_address_fallback',
                'user_id' => null,
            ]);

            // Ensure fallback address is not also in CC
            $cc = $cc->reject(fn($r) => $r['email'] === $fromAddress)->values();
            $fallbackToUsed = true;
        }

        return [
            'to' => $to,
            'cc' => $cc,
            'to_emails' => $to->pluck('email')->all(),
            'cc_emails' => $cc->pluck('email')->all(),
            'internal' => $recipients->where('type', 'internal')->values(),
            'external' => $recipients->where('type', 'external')->values(),
            'fallback_to_used' => $fallbackToUsed,
            'has_recipients' => $recipients->isNotEmpty(),
        ];
    }

    /**
     * Create a single delivery record for a parent report log based on resolved recipients.
     */
    public function createDeliveryForLog(DailyWorkReportLog $log, array|Collection $recipients): DailyWorkReportDelivery
    {
        $partitioned = is_array($recipients) && isset($recipients['to'])
            ? $recipients
            : $this->partitionRecipients(collect($recipients));

        $toEmails = collect($partitioned['to'])->pluck('email')->filter()->values()->all();
        $ccEmails = collect($partitioned['cc'])->pluck('email')->filter()->values()->all();

        $primaryTo = $toEmails[0] ?? (string) config('mail.from.address', 'noreply@example.com');
        $toCount = count($toEmails);
        $recipientName = $toCount > 1
            ? 'Project Report Recipients (' . $toCount . ')'
            : (collect($partitioned['to'])->first()['name'] ?? 'Project Report Recipient');

        return DailyWorkReportDelivery::firstOrCreate(
            [
                'daily_work_report_log_id' => $log->id,
                'recipient_email' => $primaryTo,
            ],
            [
                'recipient_name' => $recipientName,
                'recipient_type' => 'project_report',
                'recipient_sources' => [
                    'to' => $toEmails,
                    'cc' => $ccEmails,
                    'fallback_to_used' => $partitioned['fallback_to_used'] ?? false,
                ],
                'status' => 'pending',
            ]
        );
    }

    /**
     * Create delivery records for a parent report log based on resolved recipients (returns single delivery in collection).
     */
    public function createDeliveriesForLog(DailyWorkReportLog $log, Collection|array $recipients): Collection
    {
        $delivery = $this->createDeliveryForLog($log, $recipients);
        return collect([$delivery]);
    }

    /**
     * Dispatch queue jobs for pending or queued deliveries of a report log.
     */
    public function dispatchDeliveries(DailyWorkReportLog $log): int
    {
        $deliveries = $log->deliveries()
            ->whereIn('status', ['pending', 'queued'])
            ->get();

        if ($deliveries->isEmpty()) {
            return 0;
        }

        $log->update(['status' => 'queued']);

        $dispatchedCount = 0;
        foreach ($deliveries as $delivery) {
            $delivery->update([
                'status' => 'queued',
                'last_error' => null,
            ]);

            SendProjectReportDelivery::dispatch($delivery->id);
            $dispatchedCount++;
        }

        return $dispatchedCount;
    }

    /**
     * Determine whether a Project Report configuration is due for execution.
     */
    public function isConfigurationDue(DailyWorkReportSetting $setting, ?Carbon $now = null): bool
    {
        if ($setting->report_key !== 'project_report' || ! $setting->enabled) {
            return false;
        }

        $timezone = $setting->timezone ?: config('constants.timezone', config('app.timezone'));
        $currentTime = ($now ? $now->copy() : Carbon::now($timezone))->setTimezone($timezone);

        $frequency = strtolower((string) ($setting->frequency ?: 'daily'));

        // 1. Day of Week / Month checks
        if ($frequency === 'weekly') {
            $weeklyDay = (int) ($setting->weekly_day ?: 1); // 1 (Mon) to 7 (Sun)
            if ((int) $currentTime->dayOfWeekIso !== $weeklyDay) {
                return false;
            }
        } elseif ($frequency === 'monthly') {
            $monthlyDay = $setting->settings['schedule']['monthly_day']
                ?? $setting->settings['monthly_day']
                ?? null;

            if ($monthlyDay === 'last') {
                if ($currentTime->day !== $currentTime->daysInMonth) {
                    return false;
                }
            } elseif (is_numeric($monthlyDay)) {
                if ($currentTime->day !== (int) $monthlyDay) {
                    return false;
                }
            } else {
                if ($currentTime->day !== 1) {
                    return false;
                }
            }
        }

        // 2. Delivery Time check
        $deliveryTime = substr((string) ($setting->delivery_time ?: '00:00:00'), 0, 5);
        if ($currentTime->format('H:i') < $deliveryTime) {
            return false;
        }

        // 3. Duplicate Prevention: Check if already executed for this scheduled occurrence
        $occurrenceDate = $currentTime->toDateString();
        $alreadyLogged = DailyWorkReportLog::query()
            ->where('report_setting_id', $setting->id)
            ->where('report_date', $occurrenceDate)
            ->where('report_type', $frequency)
            ->where('report_key', 'project_report')
            ->exists();

        if ($alreadyLogged) {
            return false;
        }

        return true;
    }

    /**
     * Orchestrate report data preparation, log creation, delivery records, and dispatching.
     */
    public function generateAndDeliver(DailyWorkReportSetting|int $setting, ?Carbon $referenceDate = null): ?DailyWorkReportLog
    {
        $settingModel = $setting instanceof DailyWorkReportSetting
            ? $setting
            : DailyWorkReportSetting::query()
            ->where('report_key', 'project_report')
            ->where('id', $setting)
            ->firstOrFail();

        $timezone = $settingModel->timezone ?: config('constants.timezone', config('app.timezone'));
        $now = ($referenceDate ? $referenceDate->copy() : Carbon::now($timezone))->setTimezone($timezone);

        $frequency = $settingModel->frequency ?: 'daily';
        $reportDate = $now->toDateString();

        $appTimezone = config('app.timezone', 'UTC');
        $generatedAt = ($referenceDate ? $referenceDate->copy() : Carbon::now())->setTimezone($appTimezone);

        return DB::transaction(function () use ($settingModel, $frequency, $reportDate, $now, $referenceDate, $generatedAt) {
            // Lock and check for existing log to prevent concurrent duplicate execution
            $existing = DailyWorkReportLog::query()
                ->where('report_setting_id', $settingModel->id)
                ->where('report_date', $reportDate)
                ->where('report_type', $frequency)
                ->where('report_key', 'project_report')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            // 1. Condition 1: Check Eligible Recipients
            $recipients = $this->resolveRecipients($settingModel, $reportDate);
            if ($recipients->isEmpty()) {
                return DailyWorkReportLog::create([
                    'report_setting_id' => $settingModel->id,
                    'user_id' => null,
                    'report_date' => $reportDate,
                    'period_start' => $now->copy()->startOfDay(),
                    'period_end' => $now->copy()->endOfDay(),
                    'report_type' => $frequency,
                    'report_key' => 'project_report',
                    'status' => 'skipped',
                    'report_data' => null,
                    'recipients_snapshot' => [
                        'to' => [],
                        'cc' => [],
                        'internal' => [],
                        'external' => [],
                        'fallback_to_used' => false,
                    ],
                    'generated_at' => $generatedAt,
                    'completed_at' => $generatedAt->copy(),
                    'last_error' => 'Skipped: No eligible recipients configured.',
                ]);
            }

            // 2. Prepare Report Data
            $reportData = $this->prepareReportData($settingModel, $referenceDate);

            // 3. Condition 2: Check Matching Projects
            $totalProjects = (int) data_get($reportData, 'metadata.total_projects', count($reportData['projects'] ?? []));
            $isEmpty = (bool) data_get($reportData, 'metadata.is_empty', $totalProjects === 0);

            $partitioned = $this->partitionRecipients($recipients);
            $recipientsSnapshot = [
                'to' => $partitioned['to']->values()->all(),
                'cc' => $partitioned['cc']->values()->all(),
                'internal' => $partitioned['internal']->values()->all(),
                'external' => $partitioned['external']->values()->all(),
                'fallback_to_used' => $partitioned['fallback_to_used'],
            ];

            if ($isEmpty || $totalProjects === 0) {
                return DailyWorkReportLog::create([
                    'report_setting_id' => $settingModel->id,
                    'user_id' => null,
                    'report_date' => $reportDate,
                    'period_start' => $reportData['metadata']['period_start'] ?? $now->copy()->startOfDay(),
                    'period_end' => $reportData['metadata']['period_end'] ?? $now->copy()->endOfDay(),
                    'report_type' => $frequency,
                    'report_key' => 'project_report',
                    'status' => 'skipped',
                    'report_data' => $reportData,
                    'recipients_snapshot' => $recipientsSnapshot,
                    'generated_at' => $generatedAt,
                    'completed_at' => $generatedAt->copy(),
                    'last_error' => 'Skipped: No matching projects found for the configured period and filters.',
                ]);
            }

            try {
                $log = DailyWorkReportLog::create([
                    'report_setting_id' => $settingModel->id,
                    'user_id' => null,
                    'report_date' => $reportDate,
                    'period_start' => $reportData['metadata']['period_start'] ?? $now->copy()->startOfDay(),
                    'period_end' => $reportData['metadata']['period_end'] ?? $now->copy()->endOfDay(),
                    'report_type' => $frequency,
                    'report_key' => 'project_report',
                    'status' => 'pending',
                    'report_data' => $reportData,
                    'recipients_snapshot' => $recipientsSnapshot,
                    'generated_at' => $generatedAt,
                    'completed_at' => null,
                ]);

                // Create exactly one delivery record for this report occurrence
                $this->createDeliveryForLog($log, $partitioned);
                $this->dispatchDeliveries($log);

                return $log;
            } catch (\Illuminate\Database\QueryException $e) {
                // If concurrent process already inserted, return existing record
                return DailyWorkReportLog::query()
                    ->where('report_setting_id', $settingModel->id)
                    ->where('report_date', $reportDate)
                    ->where('report_type', $frequency)
                    ->where('report_key', 'project_report')
                    ->first();
            }
        });
    }
}
