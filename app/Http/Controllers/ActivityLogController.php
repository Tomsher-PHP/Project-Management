<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ReflectionMethod;
use Spatie\Activitylog\Models\Activity;
use Throwable;

class ActivityLogController extends Controller
{
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

        $activities = Activity::query()
            ->with(['causer', 'subject'])
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
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->input('date_to')));

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
            'logNames',
            'causers',
            'eventOptions'
        ));
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
        $ignoredFields = ['created_at', 'updated_at', 'deleted_at'];
        $event = $activity->event ?? 'updated';
        $attributes = collect($activity->changes->get('attributes', []))->except($ignoredFields);
        $oldValues = collect($activity->changes->get('old', []))->except($ignoredFields);
        $subjectModel = $this->resolveActivitySubjectModel($activity);

        $rows = collect();

        if ($event === 'created') {
            $rows = $attributes->map(fn ($value, $field) => [
                'field' => Str::headline($field),
                'new' => $this->transformActivityValue($subjectModel, $field, $value),
            ])->values();
        } elseif ($event === 'updated') {
            $rows = $attributes->map(fn ($value, $field) => [
                'field' => Str::headline($field),
                'old' => $this->transformActivityValue($subjectModel, $field, $oldValues->get($field)),
                'new' => $this->transformActivityValue($subjectModel, $field, $value),
            ])->values();
        }

        return [
            'can_view' => in_array($event, ['created', 'updated'], true) && $rows->isNotEmpty(),
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
