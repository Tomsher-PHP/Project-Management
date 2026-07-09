<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppraisalAssignmentRequest;
use App\Services\AppraisalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppraisalController extends Controller
{
    protected string $pageTitle;

    public function __construct(private readonly AppraisalService $appraisalService)
    {
        $this->pageTitle = 'Appraisal';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request): View
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        return view('appraisal.index', $this->appraisalService->index($month, $year));
    }

    public function assignmentData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->appraisalService->getAssignmentData((int) $validated['month'], (int) $validated['year']),
        ]);
    }

    public function assign(AppraisalAssignmentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $validated['status'] === 'published'
            ? $this->appraisalService->publish($validated)
            : $this->appraisalService->store($validated);

        return response()->json([
            'status' => true,
            'message' => $validated['status'] === 'published'
                ? 'Appraisals published successfully.'
                : 'Appraisals saved as draft successfully.',
            'data' => $result,
        ]);
    }
}
