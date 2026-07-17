<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppraisalAssignmentRequest;
use App\Http\Requests\AppraisalReviewerAssignmentRequest;
use App\Models\Appraisal;
use App\Models\AppraisalQuestion;
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
        return view('appraisal.index', $this->appraisalService->index($request));
    }

    public function assignmentData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ]);

        $month = (int) $validated['month'];
        $year = (int) $validated['year'];

        session([
            'appraisal_filter_month' => $month,
            'appraisal_filter_year' => $year,
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->appraisalService->getAssignmentData($month, $year),
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

    public function assignReviewers(AppraisalReviewerAssignmentRequest $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Appraisal reviewers assigned successfully.',
            'data' => $this->appraisalService->assignReviewers($request->validated()),
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

    public function answerPage(Appraisal $appraisal): View
    {
        $answerData = $this->appraisalService->getAnswerForm($appraisal);
        $role = $answerData['role'];
        $categories = collect($answerData['categories'] ?? [])->map(function (array $category) use ($role) {
            $questions = collect($category['questions'] ?? []);
            $answeredCount = $questions
                ->filter(fn (array $question) => $this->isAnswerQuestionCompleted($question, $role))
                ->count();

            return [
                ...$category,
                'answered_count' => $answeredCount,
                'total_questions' => $questions->count(),
                'is_completed' => $answeredCount === $questions->count(),
            ];
        });
        $totalQuestions = $categories->sum('total_questions');
        $completedQuestions = $categories->sum('answered_count');

        return view('appraisal.answer', [
            'answerData' => $answerData,
            'categories' => $categories,
            'activeCategoryId' => $categories->first()['id'] ?? null,
            'progress' => [
                'completed' => $completedQuestions,
                'total' => $totalQuestions,
                'percentage' => $totalQuestions > 0
                    ? (int) round(($completedQuestions / $totalQuestions) * 100)
                    : 0,
                'can_submit' => $totalQuestions > 0 && $completedQuestions === $totalQuestions,
            ],
        ]);
    }

    private function isAnswerQuestionCompleted(array $question, string $role): bool
    {
        $answer = $question['answer'] ?? [];
        $review = collect($question['reviews'] ?? [])->firstWhere('is_current', true) ?? [];

        return match ($question['question_type'] ?? AppraisalQuestion::QUESTION_TYPE_RATING) {
            AppraisalQuestion::QUESTION_TYPE_ANSWER => $role !== 'assignee'
                || filled(trim((string) ($answer['answer'] ?? ''))),
            AppraisalQuestion::QUESTION_TYPE_TARGET => $role === 'assignee'
                ? $this->hasAnswerValue($answer['achieved_value'] ?? null)
                : ($role !== 'reviewer' || filled(trim((string) ($review['remark'] ?? '')))),
            default => $role === 'viewer' || $this->isRatingResponseCompleted(
                $role === 'reviewer' ? ($review['rating'] ?? null) : ($answer['rating'] ?? null),
                $role === 'reviewer' ? ($review['remark'] ?? null) : ($answer['remark'] ?? null),
            ),
        };
    }

    private function hasAnswerValue(mixed $value): bool
    {
        return $value !== null && $value !== '' && is_numeric($value);
    }

    private function isRatingResponseCompleted(mixed $rating, mixed $remark): bool
    {

        if (! is_numeric($rating)) {
            return false;
        }

        $numericRating = (float) $rating;

        return $numericRating >= 0.1
            && $numericRating <= 5.0
            && round($numericRating, 1) === $numericRating
            && filled(trim((string) $remark));
    }

    public function submitAnswers(Request $request, Appraisal $appraisal): JsonResponse
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
                    if ($value !== null && strlen(substr(strrchr((string) $value, '.'), 1)) > 1) {
                        $fail('The rating must have at most one decimal place.');
                    }
                },
            ],
            'answers.*.remark' => ['nullable', 'string'],
            'answers.*.assignee_answer' => ['nullable', 'string'],
            'answers.*.achieved_value' => ['nullable', 'numeric'],
            'overall_comment' => ['nullable', 'string'],
        ]);

        $result = $this->appraisalService->submitAnswers(
            $appraisal,
            $validated['answers'],
            $validated['overall_comment'] ?? null
        );

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
                    if ($value !== null && strlen(substr(strrchr((string) $value, '.'), 1)) > 1) {
                        $fail('The rating must have at most one decimal place.');
                    }
                },
            ],
            'answers.*.remark' => ['nullable', 'string'],
            'answers.*.assignee_answer' => ['nullable', 'string'],
            'answers.*.achieved_value' => ['nullable', 'numeric'],
            'overall_comment' => ['nullable', 'string'],
        ]);

        $result = $this->appraisalService->saveDraft(
            $appraisal,
            $validated['answers'],
            $validated['overall_comment'] ?? null
        );

        return response()->json([
            'status' => true,
            'message' => 'Draft saved successfully.',
            'data' => $result,
        ]);
    }

    public function saveComment(Request $request, Appraisal $appraisal): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string'],
        ]);

        $comment = $this->appraisalService->saveComment($appraisal, $validated['comment']);

        return response()->json([
            'status' => true,
            'message' => 'Comment saved successfully.',
            'data' => [
                'appraisal_reviewer_id' => $comment->appraisal_reviewer_id,
                'comment' => $comment->comment,
                'commentator_name' => $comment->reviewer?->reviewer?->name,
                'created_at' => $comment->created_at?->format('M d, Y h:i A'),
            ],
        ]);
    }
}
