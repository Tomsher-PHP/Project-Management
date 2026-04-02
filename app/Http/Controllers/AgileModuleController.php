<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgileModuleRequest;
use App\Models\AgileModule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AgileModuleController extends Controller
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
        $records = AgileModule::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();

        return view('settings.agile-flow.index', [
            'records' => $records,
            'perPage' => $perPage,
            'currentTab' => 'modules',
            'entityLabel' => 'Module',
            'entityPluralLabel' => 'Agile Modules',
            'createPermission' => 'agile_module.create',
            'editPermission' => 'agile_module.edit',
            'deletePermission' => 'agile_module.delete',
            'togglePermission' => 'agile_module.edit',
            'storeRoute' => route('settings.agile-modules.store'),
            'updateRouteName' => 'settings.agile-modules.update',
            'destroyRouteName' => 'settings.agile-modules.destroy',
            'toggleRoute' => 'settings.agile_module.toggleStatus',
            'indexRoute' => route('settings.agile-modules.index'),
        ]);
    }

    public function store(AgileModuleRequest $request)
    {
        $data = $this->prepareData($request);

        $agileModule = AgileModule::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Agile module created successfully.',
            'data' => $agileModule,
        ]);
    }

    public function update(AgileModuleRequest $request, AgileModule $agileModule)
    {
        $data = $this->prepareData($request);

        $agileModule->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Agile module updated successfully.',
            'data' => $agileModule,
        ]);
    }

    public function destroy(AgileModule $agileModule)
    {
        if ($agileModule->is_system) {
            return redirect()
                ->route('settings.agile-modules.index')
                ->with('error', 'System agile module cannot be deleted.');
        }

        $agileModule->delete();

        return redirect()
            ->route('settings.agile-modules.index')
            ->with('success', 'Agile module deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $agileModule = AgileModule::findOrFail($request->id);
        $agileModule->is_active = ! $agileModule->is_active;
        $agileModule->save();

        return response()->json([
            'success' => true,
            'is_active' => $agileModule->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    private function prepareData(AgileModuleRequest $request): array
    {
        $data = $request->validated();

        return $data;
    }
}
