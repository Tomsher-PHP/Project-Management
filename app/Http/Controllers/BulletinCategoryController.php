<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulletinCategoryRequest;
use App\Models\BulletinCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BulletinCategoryController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Bulletin Settings';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    /**
     * Display a listing of bulletin categories.
     */
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count', 10));

        $bulletinCategories = BulletinCategory::filter($request->all())
            ->sort($request->all())
            ->paginate($perPage)
            ->withQueryString();

        $nextSortOrder = ((int) BulletinCategory::max('sort_order')) + 1;

        return view('settings.bulletin-categories.index', compact('bulletinCategories', 'perPage', 'nextSortOrder'));
    }

    /**
     * Store a newly created bulletin category.
     */
    public function store(BulletinCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['sort_order'] = ((int) BulletinCategory::max('sort_order')) + 1;

        $bulletinCategory = BulletinCategory::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Bulletin category created successfully.',
            'data' => $bulletinCategory,
        ]);
    }

    /**
     * Update the specified bulletin category.
     */
    public function update(BulletinCategoryRequest $request, BulletinCategory $bulletinCategory): JsonResponse
    {
        $bulletinCategory->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Bulletin category updated successfully.',
            'data' => $bulletinCategory,
        ]);
    }

    /**
     * Remove the specified bulletin category.
     */
    public function destroy(BulletinCategory $bulletinCategory): JsonResponse|RedirectResponse
    {
        if ($bulletinCategory->is_system) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'System bulletin category cannot be deleted.',
                ], 422);
            }

            return redirect()
                ->route('settings.bulletin-categories.index')
                ->with('error', 'System bulletin category cannot be deleted.');
        }

        try {
            $bulletinCategory->delete();

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Bulletin category deleted successfully.',
                ]);
            }

            return redirect()
                ->route('settings.bulletin-categories.index')
                ->with('success', 'Bulletin category deleted successfully.');
        } catch (\Throwable $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to delete bulletin category.',
                ], 500);
            }

            return redirect()
                ->route('settings.bulletin-categories.index')
                ->with('error', 'Failed to delete bulletin category.');
        }
    }

    /**
     * Toggle active status of the specified bulletin category.
     */
    public function toggleStatus(Request $request): JsonResponse
    {
        $bulletinCategory = BulletinCategory::findOrFail($request->id);
        $bulletinCategory->is_active = !$bulletinCategory->is_active;
        $bulletinCategory->save();

        return response()->json([
            'success' => true,
            'is_active' => $bulletinCategory->is_active,
            'message' => 'Status updated successfully',
        ], 200);
    }
}
