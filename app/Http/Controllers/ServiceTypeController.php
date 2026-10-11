<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceTypeRequest;
use App\Models\ServiceType;
use App\Models\ServiceGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ServiceTypeController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Service Types';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $serviceTypes = ServiceType::with('serviceGroup')->filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
        $nextSortOrder = ((int) ServiceType::max('sort_order')) + 1;
        $serviceGroups = ServiceGroup::active()->get();

        return view('settings.service-types.index', compact('serviceTypes', 'perPage', 'nextSortOrder', 'serviceGroups'));
    }

    public function store(ServiceTypeRequest $request)
    {
        $data = $request->validated();
        $data['added_by'] = auth()->id();
        $data['is_active'] = $request->has('is_active') ? $request->is_active : true;

        $serviceType = ServiceType::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Service type created successfully.',
            'data' => $serviceType
        ]);
    }

    public function update(ServiceTypeRequest $request, ServiceType $serviceType)
    {
        if ($serviceType->is_system) {
            return response()->json([
                'status' => false,
                'message' => 'System service type cannot be modified.'
            ], 403);
        }

        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        $serviceType->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Service type updated successfully.',
            'data' => $serviceType
        ]);
    }

    public function destroy(ServiceType $serviceType)
    {
        if ($serviceType->is_system) {
            return redirect()
                ->route('settings.service-types.index')
                ->with('error', 'System service type cannot be deleted.');
        }

        $serviceType->delete();

        return redirect()
            ->route('settings.service-types.index')
            ->with('success', 'Service type deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $serviceType = ServiceType::findOrFail($request->id);
        
        if ($serviceType->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System service type cannot be modified.'
            ], Response::HTTP_FORBIDDEN);
        }

        $serviceType->is_active = !$serviceType->is_active;
        $serviceType->save();

        return response()->json([
            'success' => true,
            'is_active' => $serviceType->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
