<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppraisalAssignmentRequest;
use App\Models\Appraisal;
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
        $result = $this->appraisalService->assign($validated);

        return response()->json([
            'status' => true,
            'message' => $validated['status'] === 'published'
                ? 'Appraisals assigned and published successfully.'
                : 'Appraisals assigned as draft successfully.',
            'data' => $result,
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $result = $this->appraisalService->publishMany($validated);
        $message = "{$result['published_count']} " . str('appraisal')->plural($result['published_count']) . ' published successfully.';

        if ($result['skipped_count'] > 0) {
            $message .= " {$result['skipped_count']} skipped because they were not draft appraisals.";
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $result,
        ]);
    }

    public function show(Appraisal $appraisal): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->appraisalService->show($appraisal),
        ]);
    }

    public function unpublish(Appraisal $appraisal): JsonResponse
    {
        $result = $this->appraisalService->unpublish($appraisal);

        return response()->json([
            'status' => true,
            'message' => 'Appraisal unpublished successfully.',
            'data' => $result,
        ]);
    }

    public function agreeKpi(Appraisal $appraisal): JsonResponse
    {
        $result = $this->appraisalService->agreeToKpi($appraisal);

        return response()->json([
            'status' => true,
            'message' => 'KPI agreed successfully.',
            'data' => $result,
        ]);
    }

    public function answerForm(Appraisal $appraisal): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->appraisalService->getAnswerForm($appraisal),
        ]);
    }

    public function submitAnswers(Request $request, Appraisal $appraisal): JsonResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.rating' => [
                'required',
                'numeric',
                'min:0.1',
                'max:5.0',
                function ($attribute, $value, $fail) {
                    if (strlen(substr(strrchr((string)$value, "."), 1)) > 1) {
                        $fail('The rating must have at most one decimal place.');
                    }
                }
            ],
            'answers.*.remark' => ['required', 'string'],
        ]);

        $result = $this->appraisalService->submitAnswers($appraisal, $validated['answers']);

        return response()->json([
            'status' => true,
            'message' => 'Appraisal answers submitted successfully.',
            'data' => $result,
        ]);
    }

    public function saveDraft(Request $request, Appraisal $appraisal): JsonResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.rating' => [
                'nullable',
                'numeric',
                'min:0.1',
                'max:5.0',
                function ($attribute, $value, $fail) {
                    if ($value !== null && strlen(substr(strrchr((string)$value, "."), 1)) > 1) {
                        $fail('The rating must have at most one decimal place.');
                    }
                }
            ],
            'answers.*.remark' => ['nullable', 'string'],
        ]);

        $result = $this->appraisalService->saveDraft($appraisal, $validated['answers']);

        return response()->json([
            'status' => true,
            'message' => 'Draft saved successfully.',
            'data' => $result,
        ]);
    }
}
