<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsProjectActivityQueries;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskNote;
use App\Models\TaskTimeLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionMethod;
use Spatie\Activitylog\Models\Activity;
use Throwable;

class ActivityLogController extends Controller
{
    use BuildsProjectActivityQueries;

    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Activity Log';
        $this->subTitle = 'Track changes across all modules';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function activityLog(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));
        $filteredProject = null;
        $filteredTask = null;

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($request->filled('project_id'), function (Builder $query) use ($request, &$filteredProject) {
                $filteredProject = Project::findOrFail((int) $request->input('project_id'));
                abort_unless(auth()->user()->can('view', $filteredProject), 403);

                $this->applyProjectActivityScope($query, $filteredProject);
            })
            ->when($request->filled('task_id'), function (Builder $query) use ($request, &$filteredTask) {
                $filteredTask = Task::query()->with('project:id,name')->findOrFail((int) $request->input('task_id'));
                abort_unless(auth()->user()->can('view', $filteredTask), 403);

                $this->applyTaskActivityScope($query, $filteredTask);
            })
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $this->applySearchFilter(
                    $query,
                    $request->string('search')->toString(),
                    $request->input('search_condition', 'contains')
                );
            })
            ->when($request->filled('log_name'), fn (Builder $query) => $query->whereIn('log_name', (array) $request->input('log_name')))
            ->when($request->filled('event'), fn (Builder $query) => $query->where('event', $request->input('event')))
            ->when($request->filled('causer_id'), function (Builder $query) use ($request) {
                $query->where('causer_type', User::class)
                    ->whereIn('causer_id', (array) $request->input('causer_id'));
            })
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->input('date_to')))
            ->when($request->filled('subject_type'), function (Builder $query) use ($request) {
                $subjectType = $this->resolveSubjectTypeFilter($request->input('subject_type'));

                if ($subjectType) {
                    $query->where('subject_type', $subjectType);
                }
            })
            ->when($request->filled('subject_id'), fn (Builder $query) => $query->where('subject_id', $request->input('subject_id')));

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['log_name', 'event', 'created_at'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $activities = $activities
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        $logNames = Activity::query()
            ->select('log_name')
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->get()
            ->map(fn (Activity $activity) => (object) [
                'id' => $activity->log_name,
                'name' => Str::headline($activity->log_name),
            ]);

        $causers = User::query()
            ->whereIn('id', Activity::query()
                ->where('causer_type', User::class)
                ->whereNotNull('causer_id')
                ->select('causer_id')
                ->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);

        $eventOptions = [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
        ];

        return view('activity-logs.index', compact(
            'activities',
            'perPage',
            'filteredProject',
            'filteredTask',
            'logNames',
            'causers',
            'eventOptions'
        ));
    }

    private function applyTaskActivityScope(Builder $query, Task $task): Builder
    {
        return $query->where(function (Builder $activityQuery) use ($task) {
            $activityQuery->where(function (Builder $subjectQuery) use ($task) {
                $subjectQuery->where('subject_type', Task::class)
                    ->where('subject_id', $task->id);
            });

            foreach ($this->getTaskActivitySubjectQueries($task) as $subjectType => $subjectIdsQuery) {
                $activityQuery->orWhere(function (Builder $subjectQuery) use ($subjectType, $subjectIdsQuery) {
                    $subjectQuery->where('subject_type', $subjectType)
                        ->whereIn('subject_id', $subjectIdsQuery);
                });
            }
        });
    }

    private function getTaskActivitySubjectQueries(Task $task): array
    {
        return [
            TaskComment::class => TaskComment::query()
                ->where('task_id', $task->id)
                ->select('id'),
            TaskNote::class => TaskNote::query()
                ->where('task_id', $task->id)
                ->select('id'),
            TaskTimeLog::class => TaskTimeLog::query()
                ->where('task_id', $task->id)
                ->select('id'),
        ];
    }

    private function applySearchFilter(Builder $query, string $search, string $condition = 'contains'): void
    {
        $query->where(function (Builder $innerQuery) use ($search, $condition) {
            $this->applyLikeCondition($innerQuery, 'description', $search, $condition);
            $this->applyLikeCondition($innerQuery, 'log_name', $search, $condition, 'orWhere');
            $this->applyLikeCondition($innerQuery, 'event', $search, $condition, 'orWhere');

            $innerQuery->orWhereHasMorph('causer', [User::class], function (Builder $causerQuery) use ($search, $condition) {
                $this->applyLikeCondition($causerQuery, 'name', $search, $condition);
            });
        });
    }

    private function resolveSubjectTypeFilter(?string $subjectType): ?string
    {
        return match ($subjectType) {
            'project' => Project::class,
            default => $subjectType,
        };
    }

    private function applyLikeCondition(
        Builder $query,
        string $column,
        string $value,
        string $condition = 'contains',
        string $method = 'where'
    ): void {
        $pattern = match ($condition) {
            'starts_with' => $value . '%',
            'ends_with' => '%' . $value,
            default => '%' . $value . '%',
        };

        $operator = $condition === 'not_contains' ? 'not like' : 'like';

        $query->{$method}($column, $operator, $pattern);
    }

    public function destroy(Activity $activity): JsonResponse
    {
        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activity log deleted successfully.',
        ]);
    }

    public function details(Activity $activity): JsonResponse
    {
        $activity->loadMissing(['causer', 'subject']);

        $details = $this->buildActivityDetails($activity);

        if (! $details['can_view']) {
            return response()->json([
                'success' => false,
                'message' => 'No detailed view is available for this activity.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'html' => view('activity-logs.partials.details-modal-content', [
                'details' => $details,
            ])->render(),
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:activity_log,id',
        ]);

        Activity::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Selected activity logs deleted successfully.',
        ]);
    }

    private function buildActivityDetails(Activity $activity): array
    {
        $ignoredFields = ['created_at', 'updated_at', 'deleted_at', 'added_by', 'updated_by'];
        $event = $activity->event ?? 'updated';
        $subjectModel = $this->resolveActivitySubjectModel($activity);
        $rows = $this->buildActivityChangeRows($activity, $subjectModel, $ignoredFields);

        return [
            'can_view' => in_array($event, ['created', 'updated', 'deleted', 'restored'], true) && $rows->isNotEmpty(),
            'event' => $event,
            'module' => Str::headline($activity->log_name ?? 'activity'),
            'subject' => $this->resolveSubjectLabel($activity),
            'subject_type' => $activity->subject_type ? Str::headline(class_basename($activity->subject_type)) : '--',
            'description' => Str::headline(str_replace('.', ' ', $activity->description)),
            'causer' => $activity->causer?->name ?? 'System',
            'logged_at' => $activity->created_at,
            'rows' => $rows,
        ];
    }

    private function buildActivityChangeRows(Activity $activity, ?Model $subjectModel, array $ignoredFields = []): Collection
    {
        $attributes = collect($activity->changes->get('attributes', []))->except($ignoredFields);
        $oldValues = collect($activity->changes->get('old', []))->except($ignoredFields);
        $labels = collect($activity->getExtraProperty('labels', []));
        $displayAttributes = collect($activity->getExtraProperty('display_attributes', []));
        $displayOld = collect($activity->getExtraProperty('display_old', []));

        return $attributes->keys()
            ->merge($oldValues->keys())
            ->unique()
            ->map(function ($field) use ($activity, $subjectModel, $attributes, $oldValues, $labels, $displayAttributes, $displayOld) {
                return [
                    'field' => $field,
                    'label' => $labels->get($field, $this->resolveActivityFieldLabel($activity, $field)),
                    'old' => $this->resolveActivityFieldValue($subjectModel, $field, $oldValues, $displayOld),
                    'new' => $this->resolveActivityFieldValue($subjectModel, $field, $attributes, $displayAttributes),
                ];
            })
            ->values();
    }

    private function resolveActivityFieldLabel(Activity $activity, string $field): string
    {
        $subjectModel = $this->resolveActivitySubjectModel($activity);

        if ($subjectModel && method_exists($subjectModel, 'getActivityAttributeLabel')) {
            return $subjectModel->getActivityAttributeLabel($field);
        }

        return (string) Str::of($field)
            ->replace('_id', '')
            ->replace('_', ' ')
            ->title();
    }

    private function resolveActivityFieldValue(?Model $subjectModel, string $field, Collection $rawValues, Collection $displayValues): array
    {
        if (! $rawValues->has($field) && ! $displayValues->has($field)) {
            return [
                'value' => null,
                'type' => null,
            ];
        }

        $resolved = $this->transformActivityValue($subjectModel, $field, $rawValues->get($field));

        if ($displayValues->has($field)) {
            $resolved['value'] = $displayValues->get($field);
        }

        return $resolved;
    }

    private function resolveSubjectLabel(Activity $activity): string
    {
        $subject = $activity->subject;

        return $subject?->name
            ?? $subject?->title
            ?? $subject?->original_name
            ?? $subject?->file_name
            ?? $subject?->project_code
            ?? $subject?->customer_code
            ?? $subject?->employee_id
            ?? ($activity->subject_id ? '#' . $activity->subject_id : '--');
    }

    private function resolveActivitySubjectModel(Activity $activity): ?Model
    {
        if ($activity->subject instanceof Model) {
            return $activity->subject;
        }

        if (! $activity->subject_type || ! class_exists($activity->subject_type)) {
            return null;
        }

        $instance = app($activity->subject_type);

        return $instance instanceof Model ? $instance : null;
    }

    private function transformActivityValue(?Model $subjectModel, string $field, mixed $value): array
    {
        if ($value === null) {
            return [
                'value' => null,
                'type' => null,
            ];
        }

        if ($subjectModel) {
            $resolvedRelationValue = $this->resolveRelationValue($subjectModel, $field, $value);

            if ($resolvedRelationValue !== null) {
                return [
                    'value' => $resolvedRelationValue,
                    'type' => 'text',
                ];
            }

            $castType = $this->detectFieldType($subjectModel, $field);

            if ($castType !== null) {
                return [
                    'value' => $value,
                    'type' => $castType,
                ];
            }
        }

        return [
            'value' => $value,
            'type' => 'text',
        ];
    }

    private function resolveRelationValue(Model $subjectModel, string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $relation = $this->resolveRelationForField($subjectModel, $field);

        if (! $relation || ! method_exists($relation, 'getRelated')) {
            return null;
        }

        $relatedModel = $relation->getRelated()->newQuery()->find($value);

        if (! $relatedModel instanceof Model) {
            return null;
        }

        return $this->resolveModelDisplayValue($relatedModel);
    }

    private function resolveRelationForField(Model $subjectModel, string $field): ?Relation
    {
        $candidateMethods = $this->buildRelationCandidates($subjectModel, $field);

        foreach ($candidateMethods as $method) {
            $relation = $this->getRelationInstance($subjectModel, $method);

            if ($relation && method_exists($relation, 'getForeignKeyName') && $relation->getForeignKeyName() === $field) {
                return $relation;
            }
        }

        return null;
    }

    private function buildRelationCandidates(Model $subjectModel, string $field): \Illuminate\Support\Collection
    {
        $baseField = Str::endsWith($field, '_id')
            ? Str::beforeLast($field, '_id')
            : $field;

        $modelPrefix = Str::snake(class_basename($subjectModel));

        return collect([
            Str::camel($field),
            Str::camel($baseField),
            Str::camel($modelPrefix . '_' . $baseField),
        ])->filter()->unique()->values();
    }

    private function getRelationInstance(Model $subjectModel, string $method): ?Relation
    {
        try {
            $reflection = new ReflectionMethod($subjectModel, $method);

            if ($reflection->getNumberOfRequiredParameters() > 0 || $reflection->isStatic()) {
                return null;
            }

            $relation = $subjectModel->{$method}();

            return $relation instanceof Relation ? $relation : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function detectFieldType(Model $subjectModel, string $field): ?string
    {
        $casts = $subjectModel->getCasts();
        $castType = $casts[$field] ?? null;

        if ($castType && in_array($castType, ['date', 'immutable_date'], true)) {
            return 'date';
        }

        if ($castType && in_array($castType, ['datetime', 'immutable_datetime'], true)) {
            return 'datetime';
        }

        if (Str::endsWith($field, '_date')) {
            return 'date';
        }

        if (Str::endsWith($field, '_at')) {
            return 'datetime';
        }

        return null;
    }

    private function resolveModelDisplayValue(Model $model): string
    {
        return $model->name
            ?? $model->title
            ?? $model->original_name
            ?? $model->file_name
            ?? $model->project_code
            ?? $model->customer_code
            ?? $model->employee_id
            ?? $model->email
            ?? ('#' . $model->getKey());
    }
}
