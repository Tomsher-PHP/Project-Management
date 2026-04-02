<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DepartmentController extends Controller
{
    protected $pageTitle;
    protected $subTitle;

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

        return view('settings.departments.index', compact('departments', 'perPage'));
    }

    public function store(DepartmentRequest $request)
    {
        $department = activity()->withoutLogs(fn () => Department::create($request->validated()));

        return response()->json([
            'status' => true,
            'message' => 'Department created successfully.',
            'data' => $department
        ]);
    }

    public function update(DepartmentRequest $request, Department $department)
    {
        activity()->withoutLogs(fn () => $department->update($request->validated()));

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

        activity()->withoutLogs(fn () => $department->delete());

        return redirect()
            ->route('settings.departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $department = Department::findOrFail($request->id);
        $department->is_active = !$department->is_active;
        activity()->withoutLogs(fn () => $department->save());

        return response()->json([
            'success' => true,
            'is_active' => $department->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
