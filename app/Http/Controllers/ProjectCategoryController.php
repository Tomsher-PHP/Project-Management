<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectCategoryRequest;
use App\Models\ProjectCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectCategoryController extends Controller
{
    protected $pageTitle;
    protected $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Project Categories';
        $this->subTitle = 'Manage your project categories';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $projectCategories = ProjectCategory::orderBy('order', 'asc')->paginate($perPage)->withQueryString();

        return view('settings.project-categories.index', compact('projectCategories', 'perPage'));
    }

    public function store(ProjectCategoryRequest $request)
    {
        $projectCategory = ProjectCategory::create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Project category created successfully.',
            'data' => $projectCategory
        ]);
    }

    public function update(ProjectCategoryRequest $request, ProjectCategory $projectCategory)
    {
        $projectCategory->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Project category updated successfully.',
            'data' => $projectCategory
        ]);
    }

    public function destroy(ProjectCategory $projectCategory)
    {
        if ($projectCategory->default) {
            return redirect()
                ->route('settings.project_categories.index')
                ->with('error', 'Default project category cannot be deleted.');
        }

        $projectCategory->delete();

        return redirect()
            ->route('settings.project-categories.index')
            ->with('success', 'Project category deleted successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $projectCategory = ProjectCategory::findOrFail($request->id);
        $projectCategory->status = !$projectCategory->status;
        $projectCategory->save();

        return response()->json([
            'success' => true,
            'status' => $projectCategory->status,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }
}
