<?php

namespace App\Http\Controllers;

use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\TaskMode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TaskSettingsController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Task Settings';
        $this->subTitle = 'Manage reusable task statuses, types and modes for your projects';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $createPermission = 'task_settings.create';
        $editPermission = 'task_settings.edit';
        $deletePermission = 'task_settings.delete';
        $togglePermission = 'task_settings.edit';

        if ($request->routeIs('settings.task-statuses.index')) {
            $records = TaskStatus::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
            $nextSortOrder = ((int) TaskStatus::max('sort_order')) + 1;
            $currentTab = 'statuses';
            $entityLabel = 'Status';
            $entityPluralLabel = 'Task Statuses';
            $storeRoute = route('settings.task-statuses.store');
            $updateRouteName = 'settings.task-statuses.update';
            $destroyRouteName = 'settings.task-statuses.destroy';
            $toggleRoute = 'settings.task_status.toggleStatus';
            $projectFlows = config('project_constants.project_flows');
            $taskStatusTypes = config('project_constants.task_status_types');
        } elseif ($request->routeIs('settings.task-types.index')) {
            $records = TaskType::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
            $nextSortOrder = ((int) TaskType::max('sort_order')) + 1;
            $currentTab = 'types';
            $entityLabel = 'Type';
            $entityPluralLabel = 'Task Types';
            $storeRoute = route('settings.task-types.store');
            $updateRouteName = 'settings.task-types.update';
            $destroyRouteName = 'settings.task-types.destroy';
            $toggleRoute = 'settings.task_type.toggleStatus';
            $taskStatusTypes = [];
        } elseif ($request->routeIs('settings.task-modes.index')) {
            $records = TaskMode::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
            $nextSortOrder = ((int) TaskMode::max('sort_order')) + 1;
            $currentTab = 'modes';
            $entityLabel = 'Mode';
            $entityPluralLabel = 'Task Modes';
            $storeRoute = route('settings.task-modes.store');
            $updateRouteName = 'settings.task-modes.update';
            $destroyRouteName = 'settings.task-modes.destroy';
            $toggleRoute = 'settings.task_mode.toggleStatus';
            $taskStatusTypes = [];
        } else {
            abort(403);
        }

        return view('settings.task-settings.index', [
            'records' => $records,
            'perPage' => $perPage,
            'nextSortOrder' => $nextSortOrder,
            'currentTab' => $currentTab,
            'entityLabel' => $entityLabel,
            'entityPluralLabel' => $entityPluralLabel,
            'createPermission' => $createPermission,
            'editPermission' => $editPermission,
            'deletePermission' => $deletePermission,
            'togglePermission' => $togglePermission,
            'storeRoute' => $storeRoute,
            'updateRouteName' => $updateRouteName,
            'destroyRouteName' => $destroyRouteName,
            'toggleRoute' => $toggleRoute,
            'projectFlows' => $projectFlows ?? [],
            'taskStatusTypes' => $taskStatusTypes,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->routeIs('settings.task-statuses.store')) {
            $data = app(\App\Http\Requests\TaskStatusRequest::class)->validated();
            $data['is_completed'] = $request->boolean('is_completed');
            $data['is_default'] = $request->boolean('is_default');
            $taskStatus = DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(TaskStatus::class, null, [
                        'flow_type' => $data['flow_type'],
                    ]);
                }

                return TaskStatus::create($data);
            });

            return response()->json([
                'status' => true,
                'message' => 'Task status created successfully.',
                'data' => $taskStatus,
            ]);
        } elseif ($request->routeIs('settings.task-types.store')) {
            $data = app(\App\Http\Requests\TaskTypeRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');
            $taskType = DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(TaskType::class);
                }

                return TaskType::create($data);
            });

            return response()->json([
                'status' => true,
                'message' => 'Task type created successfully.',
                'data' => $taskType,
            ]);
        } elseif ($request->routeIs('settings.task-modes.store')) {
            $data = app(\App\Http\Requests\TaskModeRequest::class)->validated();
            $data['is_rework'] = $request->boolean('is_rework');
            $data['is_productive'] = $request->boolean('is_productive');
            $data['track_performance'] = $request->boolean('track_performance');
            $data['customer_request'] = $request->boolean('customer_request');
            $data['is_default'] = $request->boolean('is_default');
            $taskMode = DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(TaskMode::class);
                }

                return TaskMode::create($data);
            });

            return response()->json([
                'status' => true,
                'message' => 'Task mode created successfully.',
                'data' => $taskMode,
            ]);
        }

        abort(403);
    }

    public function update(Request $request, $id)
    {
        if ($request->routeIs('settings.task-statuses.update')) {
            $data = app(\App\Http\Requests\TaskStatusRequest::class)->validated();
            $data['is_completed'] = $request->boolean('is_completed');
            $data['is_default'] = $request->boolean('is_default');

            $taskStatus = TaskStatus::findOrFail($id);
            $taskStatus = DB::transaction(function () use ($taskStatus, $data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(TaskStatus::class, $taskStatus->id, [
                        'flow_type' => $data['flow_type'],
                    ]);
                }

                $taskStatus->update($data);

                return $taskStatus->refresh();
            });

            return response()->json([
                'status' => true,
                'message' => 'Task status updated successfully.',
                'data' => $taskStatus,
            ]);
        } elseif ($request->routeIs('settings.task-types.update')) {
            $data = app(\App\Http\Requests\TaskTypeRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');

            $taskType = TaskType::findOrFail($id);
            $taskType = DB::transaction(function () use ($taskType, $data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(TaskType::class, $taskType->id);
                }

                $taskType->update($data);

                return $taskType->refresh();
            });

            return response()->json([
                'status' => true,
                'message' => 'Task type updated successfully.',
                'data' => $taskType,
            ]);
        } elseif ($request->routeIs('settings.task-modes.update')) {
            $data = app(\App\Http\Requests\TaskModeRequest::class)->validated();
            $data['is_rework'] = $request->boolean('is_rework');
            $data['is_productive'] = $request->boolean('is_productive');
            $data['track_performance'] = $request->boolean('track_performance');
            $data['customer_request'] = $request->boolean('customer_request');
            $data['is_default'] = $request->boolean('is_default');

            $taskMode = TaskMode::findOrFail($id);
            $taskMode = DB::transaction(function () use ($taskMode, $data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(TaskMode::class, $taskMode->id);
                }

                $taskMode->update($data);

                return $taskMode->refresh();
            });

            return response()->json([
                'status' => true,
                'message' => 'Task mode updated successfully.',
                'data' => $taskMode,
            ]);
        }

        abort(403);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->routeIs('settings.task-statuses.destroy')) {
            $record = TaskStatus::findOrFail($id);
            $routeName = 'settings.task-statuses.index';
            $entityName = 'Task status';
        } elseif ($request->routeIs('settings.task-types.destroy')) {
            $record = TaskType::findOrFail($id);
            $routeName = 'settings.task-types.index';
            $entityName = 'Task type';
        } elseif ($request->routeIs('settings.task-modes.destroy')) {
            $record = TaskMode::findOrFail($id);
            $routeName = 'settings.task-modes.index';
            $entityName = 'Task mode';
        } else {
            abort(403);
        }

        if ($record->is_system) {
            return redirect()
                ->route($routeName)
                ->with('error', "System {$entityName} cannot be deleted.");
        }

        $record->delete();

        return redirect()
            ->route($routeName)
            ->with('success', "{$entityName} deleted successfully.");
    }

    public function toggleStatusTaskStatus(Request $request)
    {
        $record = TaskStatus::findOrFail($request->id);
        $record->is_active = ! $record->is_active;
        $record->save();

        return response()->json([
            'success' => true,
            'is_active' => $record->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    public function toggleStatusTaskType(Request $request)
    {
        $record = TaskType::findOrFail($request->id);
        $record->is_active = ! $record->is_active;
        $record->save();

        return response()->json([
            'success' => true,
            'is_active' => $record->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    public function toggleStatusTaskMode(Request $request)
    {
        $record = TaskMode::findOrFail($request->id);
        $record->is_active = ! $record->is_active;
        $record->save();

        return response()->json([
            'success' => true,
            'is_active' => $record->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    protected function clearExistingDefaults(string $modelClass, ?int $exceptId = null, array $scope = []): void
    {
        $query = $modelClass::query();

        foreach ($scope as $column => $value) {
            $query->where($column, $value);
        }

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->update([
            'is_default' => false,
        ]);
    }
}
