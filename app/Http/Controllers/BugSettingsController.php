<?php

namespace App\Http\Controllers;

use App\Models\BugStatus;
use App\Models\DeveloperStatus;
use App\Models\QaStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\BugStatusRequest;
use App\Http\Requests\DeveloperStatusRequest;
use App\Http\Requests\QaStatusRequest;

class BugSettingsController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Bug Settings';

        view()->share([
            'pageTitle' => $this->pageTitle,
        ]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input(
            'per_page',
            config('constants.per_page_count')
        );

        $createPermission = 'bug_settings.create';
        $editPermission = 'bug_settings.edit';
        $deletePermission = 'bug_settings.delete';
        $togglePermission = 'bug_settings.edit';

        if ($request->routeIs('settings.bug-statuses.index')) {
            $records = BugStatus::filter($request->all())
                ->sort($request->all())
                ->paginate($perPage)
                ->withQueryString();

            $nextSortOrder = ((int) BugStatus::max('sort_order')) + 1;

            $currentTab = 'statuses';
            $entityLabel = 'Status';
            $entityPluralLabel = 'Bug Statuses';

            $storeRoute = route('settings.bug-statuses.store');
            $updateRouteName = 'settings.bug-statuses.update';
            $destroyRouteName = 'settings.bug-statuses.destroy';
            $toggleRoute = 'settings.bug_status.toggleStatus';

        } elseif ($request->routeIs('settings.developer-statuses.index')) {
            $records = DeveloperStatus::filter($request->all())
                ->sort($request->all())
                ->paginate($perPage)
                ->withQueryString();

            $nextSortOrder = ((int) DeveloperStatus::max('sort_order')) + 1;

            $currentTab = 'types';
            $entityLabel = 'Status';
            $entityPluralLabel = 'Developer Statuses';

            $storeRoute = route('settings.developer-statuses.store');
            $updateRouteName = 'settings.developer-statuses.update';
            $destroyRouteName = 'settings.developer-statuses.destroy';
            $toggleRoute = 'settings.developer_status.toggleStatus';

        } elseif ($request->routeIs('settings.qa-statuses.index')) {
            $records = QaStatus::filter($request->all())
                ->sort($request->all())
                ->paginate($perPage)
                ->withQueryString();

            $nextSortOrder = ((int) QaStatus::max('sort_order')) + 1;

            $currentTab = 'modes';
            $entityLabel = 'Status';
            $entityPluralLabel = 'QA Statuses';

            $storeRoute = route('settings.qa-statuses.store');
            $updateRouteName = 'settings.qa-statuses.update';
            $destroyRouteName = 'settings.qa-statuses.destroy';
            $toggleRoute = 'settings.qa_status.toggleStatus';

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
            'projectFlows' => [],
            'taskStatusTypes' => [],
        ]);
    }

    public function store(Request $request)
    {
        if ($request->routeIs('settings.bug-statuses.store')) {
            $data = app(BugStatusRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');

            $record = DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(BugStatus::class);
                }

                return BugStatus::create($data);
            });

            $message = 'Bug status created successfully.';

        } elseif ($request->routeIs('settings.developer-statuses.store')) {
            $data = app(DeveloperStatusRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');

            $record = DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(DeveloperStatus::class);
                }

                return DeveloperStatus::create($data);
            });

            $message = 'Developer status created successfully.';

        } elseif ($request->routeIs('settings.qa-statuses.store')) {
            $data = app(QaStatusRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');

            $record = DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(QaStatus::class);
                }

                return QaStatus::create($data);
            });

            $message = 'QA status created successfully.';

        } else {
            abort(403);
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $record,
        ]);
    }

    public function update(Request $request, $id)
    {
        if ($request->routeIs('settings.bug-statuses.update')) {
            $modelClass = BugStatus::class;
            $requestClass = BugStatusRequest::class;
            $message = 'Bug status updated successfully.';

        } elseif ($request->routeIs('settings.developer-statuses.update')) {
            $modelClass = DeveloperStatus::class;
            $requestClass = DeveloperStatusRequest::class;
            $message = 'Developer status updated successfully.';

        } elseif ($request->routeIs('settings.qa-statuses.update')) {
            $modelClass = QaStatus::class;
            $requestClass = QaStatusRequest::class;
            $message = 'QA status updated successfully.';

        } else {
            abort(403);
        }

        $data = app($requestClass)->validated();
        $data['is_default'] = $request->boolean('is_default');

        $record = $modelClass::findOrFail($id);

        $record = DB::transaction(function () use ($modelClass, $record, $data) {
            if ($data['is_default']) {
                $this->clearExistingDefaults(
                    $modelClass,
                    $record->id
                );
            }

            $record->update($data);

            return $record->refresh();
        });

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $record,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->routeIs('settings.bug-statuses.destroy')) {
            $modelClass = BugStatus::class;
            $routeName = 'settings.bug-statuses.index';
            $entityName = 'Bug status';

        } elseif ($request->routeIs('settings.developer-statuses.destroy')) {
            $modelClass = DeveloperStatus::class;
            $routeName = 'settings.developer-statuses.index';
            $entityName = 'Developer status';

        } elseif ($request->routeIs('settings.qa-statuses.destroy')) {
            $modelClass = QaStatus::class;
            $routeName = 'settings.qa-statuses.index';
            $entityName = 'QA status';

        } else {
            abort(403);
        }

        $record = $modelClass::findOrFail($id);

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

    public function toggleStatusBugStatus(Request $request)
    {
        return $this->toggleStatus(BugStatus::class, $request);
    }

    public function toggleStatusDeveloperStatus(Request $request)
    {
        return $this->toggleStatus(DeveloperStatus::class, $request);
    }

    public function toggleStatusQaStatus(Request $request)
    {
        return $this->toggleStatus(QaStatus::class, $request);
    }

    protected function toggleStatus(string $modelClass, Request $request)
    {
        $record = $modelClass::findOrFail($request->id);

        $record->is_active = ! $record->is_active;
        $record->save();

        return response()->json([
            'success' => true,
            'is_active' => $record->is_active,
            'message' => 'Status updated successfully.',
        ], Response::HTTP_OK);
    }

    protected function clearExistingDefaults(
        string $modelClass,
        ?int $exceptId = null
    ): void {
        $query = $modelClass::query();

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->update([
            'is_default' => false,
        ]);
    }
}
