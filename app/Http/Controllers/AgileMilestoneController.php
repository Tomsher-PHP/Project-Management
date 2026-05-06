<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgileMilestoneRequest;
use App\Models\AgileMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AgileMilestoneController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Agile Flow';
        $this->subTitle = 'Manage reusable agile milestones and sprints for your project workflow';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));
        $records = AgileMilestone::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
        $nextSortOrder = ((int) AgileMilestone::max('sort_order')) + 1;

        return view('settings.agile-flow.index', [
            'records' => $records,
            'perPage' => $perPage,
            'nextSortOrder' => $nextSortOrder,
            'currentTab' => 'milestones',
            'entityLabel' => 'Milestone',
            'entityPluralLabel' => 'Agile milestones',
            'createPermission' => 'agile_milestone.create',
            'editPermission' => 'agile_milestone.edit',
            'deletePermission' => 'agile_milestone.delete',
            'togglePermission' => 'agile_milestone.edit',
            'storeRoute' => route('settings.agile-milestones.store'),
            'updateRouteName' => 'settings.agile-milestones.update',
            'destroyRouteName' => 'settings.agile-milestones.destroy',
            'toggleRoute' => 'settings.agile_milestone.toggleStatus',
            'indexRoute' => route('settings.agile-milestones.index'),
        ]);
    }

    public function store(AgileMilestoneRequest $request)
    {
        $data = $this->prepareData($request);

        $AgileMilestone = AgileMilestone::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Agile milestone created successfully.',
            'data' => $AgileMilestone,
        ]);
    }

    public function update(AgileMilestoneRequest $request, AgileMilestone $AgileMilestone)
    {
        $data = $this->prepareData($request);

        $AgileMilestone->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Agile milestone updated successfully.',
            'data' => $AgileMilestone,
        ]);
    }

    public function destroy(AgileMilestone $AgileMilestone)
    {
        if ($AgileMilestone->is_system) {
            return redirect()
                ->route('settings.agile-milestones.index')
                ->with('error', 'System agile milestone cannot be deleted.');
        }

        $AgileMilestone->delete();

        return redirect()
            ->route('settings.agile-milestones.index')
            ->with('success', 'Agile milestone deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $AgileMilestone = AgileMilestone::findOrFail($request->id);
        $AgileMilestone->is_active = ! $AgileMilestone->is_active;
        $AgileMilestone->save();

        return response()->json([
            'success' => true,
            'is_active' => $AgileMilestone->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    private function prepareData(AgileMilestoneRequest $request): array
    {
        $data = $request->validated();

        return $data;
    }
}
