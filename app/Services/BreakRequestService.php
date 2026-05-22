<?php

namespace App\Services;

use App\Jobs\ProcessApprovedBreakWorkRequestsJob;
use App\Models\BreakWorkRequest;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\TaskMode;
use App\Models\TaskStatus;
use App\Models\TaskTimeLog;
use App\Models\TaskType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class BreakRequestService
{
    public function __construct(
        private readonly ProjectTimeService $projectTimeService
    ) {}

    public function store(User $user, string $workDate, Carbon $startedAt, Carbon $endedAt, int $durationSeconds, string $description): BreakWorkRequest
    {
        return BreakWorkRequest::create([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
            'description' => $description,
            'status' => BreakWorkRequest::STATUS_PENDING,
            'processing_status' => BreakWorkRequest::PROCESSING_STATUS_PENDING,
            'added_by' => $user->id,
        ]);
    }

    public function updatePendingRequest(BreakWorkRequest $breakWorkRequest, Carbon $startedAt, Carbon $endedAt, int $durationSeconds, string $description, string $workDate, int $updatedBy): BreakWorkRequest
    {
        $breakWorkRequest->update([
            'work_date' => $workDate,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
            'description' => $description,
            'updated_by' => $updatedBy,
        ]);

        return $breakWorkRequest->fresh();
    }

    public function getRequestsForUser(User $user, int $perPage, string $status = BreakWorkRequest::STATUS_PENDING, array $filters = []): LengthAwarePaginator
    {
        $query = $this->visibleRequestQuery($user)
            ->where('status', $status)
            ->with([
                'user:id,name',
                'user.primaryAttachment',
                'approvedBy:id,name',
                'rejectedBy:id,name',
            ]);

        $this->applyFilters($query, $filters);

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function getFilterOptions(User $user): array
    {
        $userIds = $this->visibleRequestQuery($user)->distinct()->pluck('user_id')->filter();

        return [
            'users' => $userIds->isEmpty()
                ? collect()
                : User::query()->whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function handleAction(User $user, BreakWorkRequest $breakWorkRequest, string $action, ?string $reason = null): void
    {
        abort_unless($this->canHandleRequest($user, $breakWorkRequest), Response::HTTP_FORBIDDEN);

        if (! $breakWorkRequest->isPending()) {
            throw ValidationException::withMessages([
                'break_request' => 'Only pending break work requests can be reviewed.',
            ]);
        }

        if ($action === 'approve') {
            $approvedId = DB::transaction(
                fn() => $this->approve($user, $breakWorkRequest)
            );

            ProcessApprovedBreakWorkRequestsJob::dispatch([$approvedId]);

            return;
        }

        DB::transaction(fn() => $this->reject($user, $breakWorkRequest, (string) $reason));
    }

    public function handleBulkAction(User $user, array $breakRequestIds, string $action, ?string $reason = null): int
    {
        $breakRequestIds = collect($breakRequestIds)->map(fn($id) => (int) $id)->unique()->values();

        abort_if($breakRequestIds->isEmpty(), Response::HTTP_UNPROCESSABLE_ENTITY, 'Please select at least one break work request.');

        $breakRequests = $this->accountableRequestQuery($user)
            ->whereIn('id', $breakRequestIds)
            ->where('status', BreakWorkRequest::STATUS_PENDING)
            ->get();

        abort_unless($breakRequests->count() === $breakRequestIds->count(), Response::HTTP_FORBIDDEN);

        if ($action === 'approve') {
            $approvedIds = DB::transaction(function () use ($user, $breakRequests) {
                $approvedIds = [];
                $failedMessages = [];

                foreach ($breakRequests as $breakRequest) {
                    try {
                        $approvedIds[] = $this->approve($user, $breakRequest);
                    } catch (ValidationException $exception) {
                        $message = collect($exception->errors())
                            ->flatten()
                            ->filter()
                            ->first();

                        $failedMessages[] = $message ?: "Break work request #{$breakRequest->id} could not be approved.";
                    }
                }

                if ($approvedIds === []) {
                    throw ValidationException::withMessages([
                        'break_request' => $failedMessages !== []
                            ? $failedMessages
                            : ['No selected break work requests could be approved.'],
                    ]);
                }

                return $approvedIds;
            });

            ProcessApprovedBreakWorkRequestsJob::dispatch($approvedIds);

            return count($approvedIds);
        }

        DB::transaction(function () use ($user, $breakRequests, $reason) {
            foreach ($breakRequests as $breakRequest) {
                $this->reject($user, $breakRequest, (string) $reason);
            }
        });

        return $breakRequests->count();
    }

    public function processApprovedRequest(BreakWorkRequest $breakWorkRequest): void
    {
        $breakWorkRequest->loadMissing('user:id,name');

        $breakWorkRequest->update([
            'processing_status' => BreakWorkRequest::PROCESSING_STATUS_PROCESSING,
            'process_error' => null,
        ]);

        try {
            DB::transaction(function () use ($breakWorkRequest) {
                $miscProject = $this->getOrCreateMiscProject($breakWorkRequest);
                $task = $this->createApprovedBreakTask($miscProject, $breakWorkRequest);
                $taskTimeLog = $this->createApprovedBreakTaskTimeLog($task, $breakWorkRequest);

                $this->projectTimeService->recalculateByTask($task->id);

                $breakWorkRequest->update([
                    'task_id' => $task->id,
                    'task_time_log_id' => $taskTimeLog->id,
                    'processing_status' => BreakWorkRequest::PROCESSING_STATUS_COMPLETED,
                    'processed_at' => now(),
                    'process_error' => null,
                ]);
            });
        } catch (Throwable $exception) {
            $breakWorkRequest->update([
                'task_id' => null,
                'task_time_log_id' => null,
                'processing_status' => BreakWorkRequest::PROCESSING_STATUS_FAILED,
                'processed_at' => null,
                'process_error' => $exception->getMessage(),
            ]);
        }
    }

    public function visibleRequestQuery(User $user): Builder
    {
        if ($user->is_super_admin) {
            return BreakWorkRequest::query();
        }

        $accessibleUserIds = User::query()
            ->accessibleBy($user)
            ->pluck('users.id')
            ->push($user->id)
            ->unique()
            ->values()
            ->all();

        return BreakWorkRequest::query()->whereIn('user_id', $accessibleUserIds);
    }

    private function accountableRequestQuery(User $user): Builder
    {
        if ($user->is_super_admin) {
            return BreakWorkRequest::query();
        }

        $accessibleUserIds = User::query()
            ->accessibleBy($user)
            ->pluck('users.id')
            ->unique()
            ->values()
            ->all();

        return BreakWorkRequest::query()->whereIn('user_id', $accessibleUserIds);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query->when($filters['user_id'] ?? null, fn(Builder $builder, $userIds) => $builder->whereIn('user_id', (array) $userIds));

        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $condition = (string) ($filters['search_condition'] ?? 'contains');

            $query->where(function (Builder $builder) use ($search, $condition) {
                match ($condition) {
                    'starts_with' => $builder->where('description', 'like', $search . '%'),
                    'ends_with' => $builder->where('description', 'like', '%' . $search),
                    'not_contains' => $builder->where('description', 'not like', '%' . $search . '%'),
                    default => $builder->where('description', 'like', '%' . $search . '%'),
                };
            });
        }
    }

    private function canHandleRequest(User $user, BreakWorkRequest $breakWorkRequest): bool
    {
        return $this->accountableRequestQuery($user)->whereKey($breakWorkRequest->id)->exists();
    }

    private function approve(User $user, BreakWorkRequest $breakWorkRequest): int
    {
        $this->ensureApprovalHasNoOverlap($breakWorkRequest);

        $breakWorkRequest->update([
            'status' => BreakWorkRequest::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'processing_status' => BreakWorkRequest::PROCESSING_STATUS_PENDING,
            'task_id' => null,
            'task_time_log_id' => null,
            'processed_at' => null,
            'process_error' => null,
        ]);

        return $breakWorkRequest->id;
    }

    private function reject(User $user, BreakWorkRequest $breakWorkRequest, string $reason): void
    {
        $breakWorkRequest->update([
            'status' => BreakWorkRequest::STATUS_REJECTED,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => trim($reason),
        ]);
    }

    private function ensureApprovalHasNoOverlap(BreakWorkRequest $breakWorkRequest): void
    {
        if ($this->hasOverlappingTaskTimeLog($breakWorkRequest)) {
            throw ValidationException::withMessages([
                'break_request' => 'The selected time overlaps with an existing task time log.',
            ]);
        }

        if ($this->hasOverlappingBreakWorkRequest($breakWorkRequest)) {
            throw ValidationException::withMessages([
                'break_request' => 'The selected time overlaps with another break work request.',
            ]);
        }
    }

    private function hasOverlappingTaskTimeLog(BreakWorkRequest $breakWorkRequest): bool
    {
        $startedAt = $breakWorkRequest->started_at;
        $endedAt = $breakWorkRequest->ended_at;

        if (! $startedAt || ! $endedAt) {
            return false;
        }

        return TaskTimeLog::query()
            ->where('user_id', $breakWorkRequest->user_id)
            ->whereNotNull('started_at')
            ->where(function (Builder $builder) use ($breakWorkRequest) {
                $builder
                    ->whereNull('break_work_request_id')
                    ->orWhere('break_work_request_id', '!=', $breakWorkRequest->id);
            })
            ->where(function (Builder $query) use ($startedAt, $endedAt) {
                $query
                    ->where(function (Builder $endedQuery) use ($startedAt, $endedAt) {
                        $endedQuery
                            ->whereNotNull('ended_at')
                            ->where('started_at', '<', $endedAt)
                            ->where('ended_at', '>', $startedAt);
                    })
                    ->orWhere(function (Builder $runningQuery) use ($endedAt) {
                        $runningQuery
                            ->whereNull('ended_at')
                            ->where('started_at', '<', $endedAt);
                    });
            })
            ->exists();
    }

    private function hasOverlappingBreakWorkRequest(BreakWorkRequest $breakWorkRequest): bool
    {
        $startedAt = $breakWorkRequest->started_at;
        $endedAt = $breakWorkRequest->ended_at;

        if (! $startedAt || ! $endedAt) {
            return false;
        }

        return BreakWorkRequest::query()
            ->where('user_id', $breakWorkRequest->user_id)
            ->whereKeyNot($breakWorkRequest->id)
            ->whereIn('status', [
                BreakWorkRequest::STATUS_PENDING,
                BreakWorkRequest::STATUS_APPROVED,
            ])
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->where('started_at', '<', $endedAt)
            ->where('ended_at', '>', $startedAt)
            ->exists();
    }

    private function getOrCreateMiscProject(BreakWorkRequest $breakWorkRequest): Project
    {
        $project = Project::query()
            ->where('name', 'Misc')
            ->where('is_system', true)
            ->first();

        if ($project) {
            return $project;
        }

        $statusId = $this->resolveDefaultProjectStatusId();

        if (! $statusId) {
            throw new RuntimeException('Unable to create Misc project because default project status is missing.');
        }

        $project = new Project();
        $project->project_code = Project::generateProjectCode();
        $project->name = 'Misc';
        $project->project_flow = 'linear';
        $project->priority = 'medium';
        $project->status_id = $statusId;
        $project->estimated_time_seconds = 0;
        $project->default_task_estimate_seconds = 0;
        $project->default_billable = false;
        $project->is_active = true;
        $project->is_system = true;
        $project->save();

        $addedBy = (int) ($breakWorkRequest->approved_by ?: $breakWorkRequest->user_id);

        $project->forceFill([
            'added_by' => $addedBy > 0 ? $addedBy : null,
        ])->saveQuietly();

        return $project->fresh();
    }

    private function createApprovedBreakTask(Project $project, BreakWorkRequest $breakWorkRequest): Task
    {
        $statusId = $this->resolveDefaultTaskStatusIdForFlow($project->project_flow);

        if (! $statusId) {
            throw new RuntimeException('Unable to create break task because default task status is missing.');
        }

        $taskTypeId = $this->resolveDefaultTaskTypeId();

        if (! $taskTypeId) {
            throw new RuntimeException('Unable to create break task because default task type is missing.');
        }

        $taskModeId = $this->resolveDefaultTaskModeId();

        if (! $taskModeId) {
            throw new RuntimeException('Unable to create break task because default task mode is missing.');
        }

        $task = new Task();
        $task->project_id = $project->id;
        $task->name = $this->buildTaskName($breakWorkRequest);
        $task->description = $breakWorkRequest->description;
        $task->status_id = $statusId;
        $task->task_type_id = $taskTypeId;
        $task->task_mode_id = $taskModeId;
        $task->priority = 'medium';
        $task->current_assignee_id = $breakWorkRequest->user_id;
        $task->estimated_time_seconds = (int) $breakWorkRequest->duration_seconds;
        $task->actual_time_seconds = (int) $breakWorkRequest->duration_seconds;
        $task->is_billable = false;
        $task->request_type = Task::REQUEST_TYPE_BREAK;
        $task->request_status = Task::REQUEST_APPROVED;
        $task->approved_by = $breakWorkRequest->approved_by;
        $task->approved_at = $breakWorkRequest->approved_at;
        $task->break_work_request_id = $breakWorkRequest->id;
        $task->save();

        $task->forceFill(array_filter([
            'added_by' => $breakWorkRequest->user_id ?: $breakWorkRequest->approved_by,
            'break_work_request_id' => $breakWorkRequest->id,
        ], fn($value) => $value !== null))->saveQuietly();

        return $task->fresh();
    }

    private function createApprovedBreakTaskTimeLog(Task $task, BreakWorkRequest $breakWorkRequest): TaskTimeLog
    {
        $timeLog = new TaskTimeLog();
        $timeLog->task_id = $task->id;
        $timeLog->user_id = $breakWorkRequest->user_id;
        $timeLog->started_at = $breakWorkRequest->started_at;
        $timeLog->ended_at = $breakWorkRequest->ended_at;
        $timeLog->duration_seconds = (int) $breakWorkRequest->duration_seconds;
        $timeLog->is_running = false;
        $timeLog->note = $breakWorkRequest->description;
        $timeLog->is_approved = true;
        $timeLog->approved_by = $breakWorkRequest->approved_by;
        $timeLog->approved_at = $breakWorkRequest->approved_at;
        $timeLog->break_work_request_id = $breakWorkRequest->id;
        $timeLog->save();

        $timeLog->forceFill(array_filter([
            'added_by' => $breakWorkRequest->user_id,
            'break_work_request_id' => $breakWorkRequest->id,
        ], fn($value) => $value !== null))->saveQuietly();

        return $timeLog->fresh();
    }

    private function resolveDefaultProjectStatusId(): ?int
    {
        return ProjectStatus::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderByRaw('CASE WHEN sort_order = 1 THEN 0 ELSE 1 END')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');
    }

    private function resolveDefaultTaskStatusIdForFlow(?string $flowType): ?int
    {
        if (blank($flowType)) {
            return null;
        }

        return TaskStatus::query()
            ->active()
            ->where('flow_type', $flowType)
            ->orderByDesc('is_completed')
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->value('id');
    }

    private function resolveDefaultTaskTypeId(): ?int
    {
        return TaskType::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');
    }

    private function resolveDefaultTaskModeId(): ?int
    {
        return TaskMode::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');
    }

    private function buildTaskName(BreakWorkRequest $breakWorkRequest): string
    {
        $workDate = $breakWorkRequest->work_date?->format('Y-m-d') ?: (string) $breakWorkRequest->getRawOriginal('work_date');
        $userName = trim((string) ($breakWorkRequest->user?->name ?? 'User'));
        $sequence = $this->resolveBreakSequenceForDate($breakWorkRequest);

        return sprintf('%s %s BR - %03d', $userName, $workDate, $sequence);
    }

    private function resolveBreakSequenceForDate(BreakWorkRequest $breakWorkRequest): int
    {
        return max(1, BreakWorkRequest::query()
            ->where('user_id', $breakWorkRequest->user_id)
            ->whereDate('work_date', $breakWorkRequest->work_date)
            ->whereNotNull('approved_by')
            ->whereNotNull('approved_at')
            ->count());
    }
}
