<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgileSprintRequest;
use App\Models\AgileSprint;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AgileSprintController extends Controller
{
    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Agile Flow';
        $this->subTitle = 'Manage reusable agile modules and sprints for your project workflow';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));
        $records = AgileSprint::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();

        return view('settings.agile-flow.index', [
            'records' => $records,
            'perPage' => $perPage,
            'currentTab' => 'sprints',
            'entityLabel' => 'Sprint',
            'entityPluralLabel' => 'Agile Sprints',
            'createPermission' => 'agile_sprint.create',
            'editPermission' => 'agile_sprint.edit',
            'deletePermission' => 'agile_sprint.delete',
            'togglePermission' => 'agile_sprint.edit',
            'storeRoute' => route('settings.agile-sprints.store'),
            'updateRouteName' => 'settings.agile-sprints.update',
            'destroyRouteName' => 'settings.agile-sprints.destroy',
            'toggleRoute' => 'settings.agile_sprint.toggleStatus',
            'indexRoute' => route('settings.agile-sprints.index'),
        ]);
    }

    public function store(AgileSprintRequest $request)
    {
        $data = $this->prepareData($request);

        if ($data['default']) {
            AgileSprint::query()->update(['default' => false]);
        }

        $agileSprint = AgileSprint::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Agile sprint created successfully.',
            'data' => $agileSprint,
        ]);
    }

    public function update(AgileSprintRequest $request, AgileSprint $agileSprint)
    {
        $data = $this->prepareData($request);

        if ($data['default']) {
            AgileSprint::whereKeyNot($agileSprint->id)->update(['default' => false]);
        }

        $agileSprint->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Agile sprint updated successfully.',
            'data' => $agileSprint,
        ]);
    }

    public function destroy(AgileSprint $agileSprint)
    {
        if ($agileSprint->default) {
            return redirect()
                ->route('settings.agile-sprints.index')
                ->with('error', 'Default agile sprint cannot be deleted.');
        }

        $agileSprint->delete();

        return redirect()
            ->route('settings.agile-sprints.index')
            ->with('success', 'Agile sprint deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $agileSprint = AgileSprint::findOrFail($request->id);
        $agileSprint->status = ! $agileSprint->status;
        $agileSprint->save();

        return response()->json([
            'success' => true,
            'status' => $agileSprint->status,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    private function prepareData(AgileSprintRequest $request): array
    {
        $data = $request->validated();
        $data['default'] = (bool) ($data['default'] ?? false);

        return $data;
    }
}
