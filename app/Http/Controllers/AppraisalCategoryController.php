<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppraisalCategoryRequest;
use App\Models\AppraisalCategory;
use App\Services\AppraisalSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AppraisalCategoryController extends Controller
{
    protected string $pageTitle;

    public function __construct(private readonly AppraisalSettingsService $appraisalSettingsService)
    {
        $this->pageTitle = 'Appraisal Categories';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request): View
    {
        return view('settings.appraisal-categories.index', $this->getIndexViewData());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AppraisalCategoryRequest $request): JsonResponse
    {
        $appraisalCategory = $this->appraisalSettingsService->createCategory($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Appraisal category created successfully.',
            'data' => $appraisalCategory,
            'html' => $this->renderIndexContent(),
            'render_target' => '#appraisal-category-index-content',
            'render_mode' => 'replace_inner',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AppraisalCategoryRequest $request, AppraisalCategory $appraisal): JsonResponse
    {
        $appraisalCategory = $this->appraisalSettingsService->updateCategory($appraisal, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Appraisal category updated successfully.',
            'data' => $appraisalCategory,
            'html' => $this->renderIndexContent(),
            'render_target' => '#appraisal-category-index-content',
            'render_mode' => 'replace_inner',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, AppraisalCategory $appraisal)
    {
        abort(Response::HTTP_NOT_FOUND);
    }

    public function toggleStatus(Request $request): JsonResponse
    {
        $appraisalCategory = AppraisalCategory::findOrFail($request->id);
        $appraisalCategory = $this->appraisalSettingsService->toggleCategoryStatus($appraisalCategory);

        return response()->json([
            'success' => true,
            'is_active' => $appraisalCategory->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }

    private function getIndexViewData(): array
    {
        return [
            'appraisalCategories' => $this->appraisalSettingsService->getAppraisalCategories(),
        ];
    }

    private function renderIndexContent(): string
    {
        return view('settings.appraisal-categories.partials.index-content', $this->getIndexViewData())->render();
    }
}
