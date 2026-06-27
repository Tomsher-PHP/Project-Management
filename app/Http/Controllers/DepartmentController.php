<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DepartmentController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Departments';
        $this->subTitle = 'Organize your company into structured departments for better management and reporting';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $departments = Department::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
        $nextSortOrder = ((int) Department::max('sort_order')) + 1;

        return view('settings.departments.index', compact('departments', 'perPage', 'nextSortOrder'));
    }

    public function store(DepartmentRequest $request)
    {
        $department = Department::create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Department created successfully.',
            'data' => $department
        ]);
    }

    public function update(DepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Department updated successfully.',
            'data' => $department
        ]);
    }

    public function destroy(Department $department)
    {
        if ($department->is_system) {
            return redirect()
                ->route('settings.departments.index')
                ->with('error', 'System department cannot be deleted.');
        }

        $department->delete();

        return redirect()
            ->route('settings.departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $department = Department::findOrFail($request->id);
        $department->is_active = !$department->is_active;
        $department->save();

        return response()->json([
            'success' => true,
            'is_active' => $department->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
