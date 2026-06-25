<?php

namespace App\Http\Controllers;

use App\Http\Requests\DesignationRequest;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DesignationController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Designations';
        $this->subTitle = 'Create and organize job roles for structured workforce management.';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $designations = Designation::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
        $nextSortOrder = ((int) Designation::max('sort_order')) + 1;

        return view('settings.designations.index', compact('designations', 'perPage', 'nextSortOrder'));
    }

    public function store(DesignationRequest $request)
    {
        $designation = Designation::create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Designation created successfully.',
            'data' => $designation
        ]);
    }

    public function update(DesignationRequest $request, Designation $designation)
    {
        $designation->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Designation updated successfully.',
            'data' => $designation
        ]);
    }

    public function destroy(Designation $designation)
    {
        if ($designation->is_system) {
            return redirect()
                ->route('settings.designations.index')
                ->with('error', 'System designation cannot be deleted.');
        }

        $designation->delete();

        return redirect()
            ->route('settings.designations.index')
            ->with('success', 'Department deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $designation = Designation::findOrFail($request->id);
        $designation->is_active = !$designation->is_active;
        $designation->save();

        return response()->json([
            'success' => true,
            'is_active' => $designation->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
