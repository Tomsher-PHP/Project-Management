<?php

namespace App\Http\Controllers;

use App\Http\Requests\BroadcastCategoryRequest;
use App\Models\BroadcastCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BroadcastCategoryController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Broadcast Settings';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    /**
     * Display a listing of broadcast categories.
     */
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count', 10));

        $broadcastCategories = BroadcastCategory::filter($request->all())
            ->sort($request->all())
            ->paginate($perPage)
            ->withQueryString();

        $nextSortOrder = ((int) BroadcastCategory::max('sort_order')) + 1;

        return view('settings.broadcast-categories.index', compact('broadcastCategories', 'perPage', 'nextSortOrder'));
    }

    /**
     * Store a newly created broadcast category.
     */
    public function store(BroadcastCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['code'] = $data['code'] ?? BroadcastCategory::generateUniqueCode();
        $data['is_default'] = $request->boolean('is_default');
        $data['sort_order'] = ((int) BroadcastCategory::max('sort_order')) + 1;

        $broadcastCategory = DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                $this->clearExistingDefaults();
            }

            return BroadcastCategory::create($data);
        });

        return response()->json([
            'status' => true,
            'message' => 'Broadcast category created successfully.',
            'data' => $broadcastCategory,
        ]);
    }

    /**
     * Update the specified broadcast category.
     */
    public function update(BroadcastCategoryRequest $request, BroadcastCategory $broadcastCategory): JsonResponse
    {
        $data = $request->validated();
        if (empty($broadcastCategory->code)) {
            $data['code'] = BroadcastCategory::generateUniqueCode();
        }
        $data['is_default'] = $request->boolean('is_default');

        $broadcastCategory = DB::transaction(function () use ($broadcastCategory, $data) {
            if ($data['is_default']) {
                $this->clearExistingDefaults($broadcastCategory->id);
            }

            $broadcastCategory->update($data);

            return $broadcastCategory->refresh();
        });

        return response()->json([
            'status' => true,
            'message' => 'Broadcast category updated successfully.',
            'data' => $broadcastCategory,
        ]);
    }

    /**
     * Remove the specified broadcast category.
     */
    public function destroy(BroadcastCategory $broadcastCategory): JsonResponse|RedirectResponse
    {
        if ($broadcastCategory->is_system) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'System broadcast category cannot be deleted.',
                ], 422);
            }

            return redirect()
                ->route('settings.broadcast-categories.index')
                ->with('error', 'System broadcast category cannot be deleted.');
        }

        try {
            $broadcastCategory->delete();

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Broadcast category deleted successfully.',
                ]);
            }

            return redirect()
                ->route('settings.broadcast-categories.index')
                ->with('success', 'Broadcast category deleted successfully.');
        } catch (\Throwable $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to delete broadcast category.',
                ], 500);
            }

            return redirect()
                ->route('settings.broadcast-categories.index')
                ->with('error', 'Failed to delete broadcast category.');
        }
    }

    /**
     * Toggle active status of the specified broadcast category.
     */
    public function toggleStatus(Request $request): JsonResponse
    {
        $broadcastCategory = BroadcastCategory::findOrFail($request->id);
        $broadcastCategory->is_active = !$broadcastCategory->is_active;
        $broadcastCategory->save();

        return response()->json([
            'success' => true,
            'is_active' => $broadcastCategory->is_active,
            'message' => 'Status updated successfully',
        ], 200);
    }

    /**
     * Clear is_default flag on existing broadcast categories.
     */
    protected function clearExistingDefaults(?int $exceptId = null): void
    {
        $query = BroadcastCategory::query();

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->update([
            'is_default' => false,
        ]);
    }
}

