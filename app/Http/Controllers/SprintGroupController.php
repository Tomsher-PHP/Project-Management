<?php

namespace App\Http\Controllers;

use App\Http\Requests\SprintGroupRequest;
use App\Models\SprintGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SprintGroupController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Agile Flow';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));
        $records = SprintGroup::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
        $nextSortOrder = ((int) SprintGroup::max('sort_order')) + 1;

        return view('settings.agile-flow.index', [
            'records' => $records,
            'perPage' => $perPage,
            'nextSortOrder' => $nextSortOrder,
            'currentTab' => 'sprint_groups',
            'entityLabel' => 'Sprint Group',
            'entityPluralLabel' => 'Sprint Groups',
            'createPermission' => 'project_settings.create',
            'editPermission' => 'project_settings.edit',
            'deletePermission' => 'project_settings.delete',
            'togglePermission' => 'project_settings.edit',
            'storeRoute' => route('settings.sprint-groups.store'),
            'updateRouteName' => 'settings.sprint-groups.update',
            'destroyRouteName' => 'settings.sprint-groups.destroy',
            'toggleRoute' => 'settings.sprint_group.toggleStatus',
            'indexRoute' => route('settings.sprint-groups.index'),
        ]);
    }

    public function store(SprintGroupRequest $request)
    {
        $data = $this->prepareData($request);

        $sprintGroup = SprintGroup::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Sprint group created successfully.',
            'data' => $sprintGroup,
        ]);
    }

    public function update(SprintGroupRequest $request, SprintGroup $sprintGroup)
    {
        $data = $this->prepareData($request);

        $sprintGroup->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Sprint group updated successfully.',
            'data' => $sprintGroup,
        ]);
    }

    public function destroy(SprintGroup $sprintGroup)
    {
        if ($sprintGroup->is_system) {
            return redirect()
                ->route('settings.sprint-groups.index')
                ->with('error', 'System sprint group cannot be deleted.');
        }

        if ($sprintGroup->agileSprints()->exists()) {
            return redirect()
                ->route('settings.sprint-groups.index')
                ->with('error', 'Sprint group cannot be deleted because it is assigned to one or more sprints.');
        }

        $sprintGroup->delete();

        return redirect()
            ->route('settings.sprint-groups.index')
            ->with('success', 'Sprint group deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $sprintGroup = SprintGroup::findOrFail($request->id);
        $sprintGroup->is_active = ! $sprintGroup->is_active;
        $sprintGroup->save();

        return response()->json([
            'success' => true,
            'is_active' => $sprintGroup->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    private function prepareData(SprintGroupRequest $request): array
    {
        $data = $request->validated();

        return $data;
    }
}
