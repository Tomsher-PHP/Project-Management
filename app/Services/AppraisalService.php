<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AppraisalAnswer;
use App\Models\AppraisalCategory;
use App\Models\AppraisalComment;
use App\Models\AppraisalQuestion;
use App\Models\AppraisalQuestionUnit;
use App\Models\AppraisalReviewer;
use App\Models\AppraisalSnapshotQuestion;
use App\Models\Department;
use App\Models\Kpi;
use App\Models\User;
use App\Services\Reports\Concerns\ResolvesTeamUserFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppraisalService
{
    use ResolvesTeamUserFilters;

    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(Request $request, bool $withMySummary = true): array
    {
        $month = $request->input('month');
        $year = $request->input('year');

        if ($month !== null) {
            $month = (int) $month;
            session(['appraisal_filter_month' => $month]);
        } else {
            $month = (int) session('appraisal_filter_month', now()->month);
        }

        if ($year !== null) {
            $year = (int) $year;
            session(['appraisal_filter_year' => $year]);
        } else {
            $year = (int) session('appraisal_filter_year', now()->year);
        }

        $perPage = (int) $request->input('per_page', config('constants.per_page_count', 10));

        $myAppraisalSummary = $withMySummary
            ? $this->getMyAppraisalSummary($request, $month, $year)
            : [];
        $myAppraisalsPaginator = $this->getMyAppraisalsPaginator($request, $month, $year, $perPage);
        $usersPaginator = null;

        if (auth()->user()?->can('appraisal.create')) {
            $usersPaginator = $this->getUsersWithAssignmentsPaginator($month, $year, $perPage);
        }

        $assignmentData = [
            'my_appraisals' => $myAppraisalsPaginator->items(),
            'users' => $usersPaginator ? $usersPaginator->items() : [],
            'kpis' => auth()->user()?->can('appraisal.create') ? $this->getActiveKpis() : [],
            'categories' => auth()->user()?->can('appraisal.create') ? $this->getActiveCategories() : [],
        ];

        if (auth()->user()?->can('appraisal.create')) {
            $assignmentData = array_merge($assignmentData, $this->getAssignmentQuestionOptions());
        }

        return [
            'month' => $month,
            'year' => $year,
            'months' => $this->getMonths(),
            'years' => $this->getYears($year),
            'assignmentData' => $assignmentData,
            'myAppraisalSummary' => $myAppraisalSummary,
            'myAppraisalsPaginator' => $myAppraisalsPaginator,
            'usersPaginator' => $usersPaginator,
            'perPage' => $perPage,
            'teams' => $this->getFilterTeams($request),
            'users' => $this->getFilterUsers($request),
            'departments' => $this->getFilterDepartments(),
            'kpiOptions' => [
                'agreed' => 'Agreed',
                'not_agreed' => 'Not Agreed',
            ],
            'myStatusOptions' => collect([
                Appraisal::STATUS_DRAFT => Appraisal::STATUSES[Appraisal::STATUS_DRAFT],
                Appraisal::STATUS_PUBLISHED => Appraisal::STATUSES[Appraisal::STATUS_PUBLISHED],
                Appraisal::STATUS_COMPLETED => Appraisal::STATUSES[Appraisal::STATUS_COMPLETED],
            ])->map(fn (string $name, string $id) => (object) compact('id', 'name'))->values(),
            'statusOptions' => [
                'Draft' => 'Draft',
                'Published' => 'Published',
                'Completed' => 'Completed',
                'Closed' => 'Closed',
            ],
        ];
    }

    public function getAssignmentData(int $month, int $year): array
    {
        $data = [
            'my_appraisals' => $this->getMyAppraisals($month, $year),
        ];

        if (auth()->user()?->can('appraisal.create')) {
            $data['users'] = $this->getUsersWithAssignments($month, $year);
            $data['kpis'] = $this->getActiveKpis();
            $data['categories'] = $this->getActiveCategories();
            $data = array_merge($data, $this->getAssignmentQuestionOptions());
        }

        return $data;
    }

    public function getUsersWithAssignments(int $month, int $year): array
    {
        $users = User::query()
            ->accessibleBy(auth()->user())
            ->with(['details.department', 'details.designation', 'primaryAttachment'])
            ->orderBy('name')
            ->get();

        $appraisals = Appraisal::query()
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('user_id', $users->pluck('id'))
            ->with([
                'snapshotCategories:id,appraisal_id,name,sort_order',
                'reviewers.reviewer:id,name',
            ])
            ->withCount('snapshotQuestions')
            ->get()
            ->keyBy('user_id');

        return $users->map(function (User $user) use ($appraisals) {
            $appraisal = $appraisals->get($user->id);
            $snapshotCategoryNames = $appraisal?->snapshotCategories
                ? $appraisal->snapshotCategories->pluck('name')->filter()->values()
                : collect();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image_url' => $user->profile_image_url,
                'department' => $user->details?->department?->name,
                'designation' => $user->details?->designation?->name,
                'is_assigned' => (bool) $appraisal,
                'appraisal_id' => $appraisal?->id,
                'kpi_id' => $appraisal?->kpi_id,
                'kpi_name' => $appraisal?->kpi_name,
                'status' => $appraisal?->status,
                'status_label' => $appraisal ? str($appraisal->status)->headline()->toString() : 'Not Assigned',
                'is_editable' => ! $appraisal || $appraisal->status === 'draft',
                'categories' => $snapshotCategoryNames->all(),
                'questions_count' => $appraisal?->snapshot_questions_count ?? 0,
                'reviewers' => $appraisal
                    ? $appraisal->reviewers
                        ->sortBy('level')
                        ->map(fn (AppraisalReviewer $reviewer) => [
                            'level' => $reviewer->level,
                            'name' => $reviewer->reviewer?->name,
                        ])
                        ->values()
                        ->all()
                    : [],
                'avatar_html' => \Illuminate\Support\Facades\Blade::render('<x-user-avatar :user="$user" size="md" />', ['user' => $user]),
            ];
        })->values()->all();
    }

    public function getMyAppraisals(int $month, int $year): array
    {
        $users = User::query()
            ->accessibleBy(auth()->user())
            ->active()
            ->with(['details.department', 'details.designation', 'primaryAttachment'])
            ->orderBy('name')
            ->get();

        if (auth()->id() && ! $users->contains('id', auth()->id())) {
            $authUser = User::query()
                ->with(['details.department', 'details.designation', 'primaryAttachment'])
                ->find(auth()->id());

            if ($authUser) {
                $users->prepend($authUser);
            }
        }

        $appraisals = Appraisal::query()
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('user_id', $users->pluck('id'))
            ->whereIn('status', ['published', 'completed', 'closed'])
            ->withCount(['snapshotQuestions', 'snapshotCategories'])
            ->with([
                'user.details',
                'answers:id,appraisal_id,rating,submitted_at',
                'reviewers.reviewer:id,name',
            ])
            ->get()
            ->keyBy('user_id');

        return $users
            ->map(function (User $user) use ($appraisals) {
                $appraisal = $appraisals->get($user->id);
                $answerRole = $appraisal ? $this->resolveAnswerRole($appraisal) : null;
                $canAnswer = $appraisal ? $this->canOpenAnswerForm($appraisal, $answerRole) : false;
                $reporter = $appraisal?->reviewers->firstWhere('level', 1);
                $manager = $appraisal?->reviewers->firstWhere('level', 2);
                $assigneeSubmittedAt = $appraisal?->answers
                    ->pluck('submitted_at')
                    ->filter()
                    ->max();

                return [
                    'is_assignee' => (int) $user->id === (int) auth()->id(),
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'profile_image_url' => $user->profile_image_url,
                        'department' => $user->details?->department?->name,
                        'designation' => $user->details?->designation?->name,
                        'avatar_html' => \Illuminate\Support\Facades\Blade::render('<x-user-avatar :user="$user" size="md" />', ['user' => $user]),
                    ],
                    'appraisal_id' => $appraisal?->id,
                    'kpi_name' => $appraisal?->kpi_name,
                    'kpi_description' => $appraisal?->kpi_description,
                    'questions_count' => $appraisal?->snapshot_questions_count ?? 0,
                    'categories_count' => $appraisal?->snapshot_categories_count ?? 0,
                    'current_stage' => $appraisal?->current_stage,
                    'current_stage_label' => $this->getCurrentStageLabel($appraisal, $assigneeSubmittedAt),
                    'status' => $appraisal?->status,
                    'status_label' => $appraisal ? str($appraisal->status)->headline()->toString() : null,
                    'completed_at' => $this->formatDate($appraisal?->completed_at),
                    'final_rating' => $appraisal?->final_rating,
                    'assignee_submitted_at' => $this->formatDateTime($assigneeSubmittedAt),
                    'reporter_submitted_at' => $this->formatDateTime($reporter?->submitted_at),
                    'manager_submitted_at' => $this->formatDateTime($manager?->submitted_at),
                    'assignee_average_rating' => $appraisal?->assignee_average_rating,
                    'reporter_average_rating' => $reporter?->average_rating,
                    'manager_average_rating' => $manager?->average_rating,
                    'assignee_submitted_by_id' => $appraisal?->user_id,
                    'assignee_submitted_by_name' => $appraisal?->user?->name,
                    'reporter_submitted_by_id' => $reporter?->reviewer_user_id,
                    'reporter_submitted_by_name' => $reporter?->reviewer?->name,
                    'manager_submitted_by_id' => $manager?->reviewer_user_id,
                    'manager_submitted_by_name' => $manager?->reviewer?->name,
                    'kpi_agreed_at' => $this->formatDate($appraisal?->kpi_agreed_at),
                    'kpi_agreed' => filled($appraisal?->kpi_agreed_at),
                    'can_agree' => $appraisal && $this->canAgreeToKpi($appraisal),
                    'answer_role' => $answerRole,
                    'can_answer' => $canAnswer,
                    'can_edit_answer' => $canAnswer
                        && in_array($answerRole, ['assignee', 'reviewer'], true)
                        && ! $this->isRoleSubmitted($appraisal, $answerRole),
                ];
            })
            ->values()
            ->all();
    }

    public function assign(array $data): array
    {
        $month = (int) $data['month'];
        $year = (int) $data['year'];
        $status = $data['status'];
        $userIds = collect($data['user_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $kpi = Kpi::query()
            ->active()
            ->find($data['kpi_id']);

        if (! $kpi) {
            throw ValidationException::withMessages([
                'kpi_id' => 'The selected KPI is not available.',
            ]);
        }

        $this->validateAssignableUsers($userIds, $month, $year);

        $assignedAppraisals = [];

        $savedCount = DB::transaction(function () use ($data, $kpi, $status, $month, $year, $userIds, &$assignedAppraisals) {
            $count = 0;

            $userIds->each(function (int $userId) use ($data, $kpi, $status, $month, $year, &$count, &$assignedAppraisals) {
                $appraisal = Appraisal::query()
                    ->firstOrNew([
                        'year' => $year,
                        'month' => $month,
                        'user_id' => $userId,
                    ]);

                $wasPublished = $appraisal->exists && strtolower((string) $appraisal->getOriginal('status')) === 'published';
                $isExistingDraft = $appraisal->exists && strtolower((string) $appraisal->getOriginal('status')) === 'draft';

                if (! $appraisal->exists) {
                    $appraisal->created_by = auth()->id();
                }

                $appraisal->kpi_id = $kpi->id;
                $appraisal->kpi_name = $kpi->name;
                $appraisal->kpi_description = $kpi->description;
                $appraisal->status = $status;
                $appraisal->published_at = $status === 'published' ? now() : null;
                $appraisal->published_by = $status === 'published' ? auth()->id() : null;
                $appraisal->save();

                $this->replaceSnapshot($appraisal, $data['categories'], $isExistingDraft);

                if ($status === 'published' && ! $wasPublished) {
                    $assignedAppraisals[] = $appraisal;
                }

                $count++;
            });

            return $count;
        });

        foreach ($assignedAppraisals as $appraisal) {
            $this->notificationService->notifyAppraisalAssigned(
                $appraisal,
                auth()->user(),
                (int) $appraisal->user_id,
            );
        }

        return [
            'count' => $savedCount,
            'my_appraisals' => $this->getMyAppraisals($month, $year),
            'users' => $this->getUsersWithAssignments($month, $year),
            'kpis' => $this->getActiveKpis(),
            'categories' => $this->getActiveCategories(),
            'reviewer_assignments' => $this->getReviewerAssignmentData($userIds, $month, $year),
        ];
    }

    public function assignReviewers(array $data): array
    {
        $month = (int) $data['month'];
        $year = (int) $data['year'];
        $assignments = collect($data['assignments'])->keyBy('user_id');
        $userIds = $assignments->keys()->map(fn ($id) => (int) $id)->values();

        $this->validateAssignableUsers($userIds, $month, $year);

        $appraisals = Appraisal::query()
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        if ($appraisals->count() !== $userIds->count()) {
            throw ValidationException::withMessages([
                'assignments' => 'Save every appraisal as draft before assigning reviewers.',
            ]);
        }

        foreach ($assignments as $userId => $assignment) {
            $chainIds = $this->getReviewerChainUserIds((int) $userId);
            $reviewerIds = collect($assignment['reviewer_user_ids'])
                ->map(fn ($id) => (int) $id)
                ->values();

            if (
                $reviewerIds->duplicates()->isNotEmpty()
                || $reviewerIds->count() > $chainIds->count()
                || $reviewerIds->diff($chainIds)->isNotEmpty()
            ) {
                throw ValidationException::withMessages([
                    "assignments.{$userId}.reviewer_user_ids" => 'Reviewers must be unique users from the employee reporting chain.',
                ]);
            }
        }

        DB::transaction(function () use ($assignments, $appraisals) {
            foreach ($assignments as $userId => $assignment) {
                $appraisal = $appraisals->get((int) $userId);

                $appraisal->reviewers()->withTrashed()->forceDelete();

                $appraisal->reviewers()->createMany(
                    collect($assignment['reviewer_user_ids'])
                        ->values()
                        ->map(fn ($reviewerUserId, $index) => [
                            'reviewer_user_id' => (int) $reviewerUserId,
                            'role' => 'reporter',
                            'level' => $index + 1,
                        ])
                        ->all()
                );
            }
        });

        return [
            'reviewer_assignments' => $this->getReviewerAssignmentData($userIds, $month, $year),
            'my_appraisals' => $this->getMyAppraisals($month, $year),
            'users' => $this->getUsersWithAssignments($month, $year),
        ];
    }

    public function publishMany(array $data): array
    {
        $month = (int) $data['month'];
        $year = (int) $data['year'];
        $userIds = collect($data['user_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $accessibleUserIds = User::query()
            ->accessibleBy(auth()->user())
            ->whereIn('id', $userIds)
            ->pluck('id');

        if ($accessibleUserIds->count() !== $userIds->count()) {
            throw ValidationException::withMessages([
                'user_ids' => 'One or more selected users are not available for publishing.',
            ]);
        }

        $appraisals = Appraisal::query()
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('user_id', $userIds)
            ->get();

        $draftAppraisals = $appraisals->where('status', 'draft');
        $skippedCount = $userIds->count() - $draftAppraisals->count();

        if ($draftAppraisals->isEmpty()) {
            throw ValidationException::withMessages([
                'user_ids' => 'Only draft appraisals can be published.',
            ]);
        }

        $publishedAppraisals = [];

        DB::transaction(function () use ($draftAppraisals, &$publishedAppraisals) {
            $draftAppraisals->each(function (Appraisal $appraisal) use (&$publishedAppraisals) {
                $appraisal->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'published_by' => auth()->id(),
                ]);

                $publishedAppraisals[] = $appraisal;
            });
        });

        foreach ($publishedAppraisals as $appraisal) {
            $this->notificationService->notifyAppraisalAssigned(
                $appraisal,
                auth()->user(),
                (int) $appraisal->user_id,
            );
        }

        return [
            'published_count' => $draftAppraisals->count(),
            'skipped_count' => $skippedCount,
            'my_appraisals' => $this->getMyAppraisals($month, $year),
            'users' => $this->getUsersWithAssignments($month, $year),
            'kpis' => $this->getActiveKpis(),
            'categories' => $this->getActiveCategories(),
        ];
    }

    public function show(Appraisal $appraisal): array
    {
        $this->ensureAppraisalUserIsAccessible($appraisal, 'viewing');

        if (! in_array($appraisal->status, ['draft', 'published', 'completed', 'closed'], true)) {
            throw ValidationException::withMessages([
                'appraisal' => 'This appraisal cannot be loaded.',
            ]);
        }

        return $this->formatAppraisalSnapshot(
            $appraisal->load([
                'user:id,name,email',
                'user.details.department',
                'user.details.designation',
                'snapshotCategories.questions',
                'reviewers.reviewer:id,name,email',
            ])
        );
    }

    public function unpublish(Appraisal $appraisal): array
    {
        $this->ensureAppraisalUserIsAccessible($appraisal, 'unpublishing');

        if ($appraisal->status !== 'published') {
            throw ValidationException::withMessages([
                'appraisal' => 'Only published appraisals can be unpublished.',
            ]);
        }

        $appraisal->update([
            'status' => 'draft',
            'published_at' => null,
            'published_by' => null,
        ]);

        return [
            'my_appraisals' => $this->getMyAppraisals($appraisal->month, $appraisal->year),
            'users' => $this->getUsersWithAssignments($appraisal->month, $appraisal->year),
            'kpis' => $this->getActiveKpis(),
            'categories' => $this->getActiveCategories(),
        ];
    }

    public function agreeToKpi(Appraisal $appraisal): array
    {
        if ((int) $appraisal->user_id !== (int) auth()->id()) {
            throw ValidationException::withMessages([
                'appraisal' => 'Only the appraisal assignee can agree to this KPI.',
            ]);
        }

        if ($appraisal->status !== 'published') {
            throw ValidationException::withMessages([
                'appraisal' => 'Only published appraisals can be agreed.',
            ]);
        }

        if (filled($appraisal->kpi_agreed_at)) {
            throw ValidationException::withMessages([
                'appraisal' => 'This KPI has already been agreed.',
            ]);
        }

        DB::transaction(function () use ($appraisal) {
            $appraisal->update([
                'kpi_agreed_at' => now(),
            ]);
        });

        return [
            'my_appraisals' => $this->getMyAppraisals($appraisal->month, $appraisal->year),
        ];
    }

    public function getAnswerForm(Appraisal $appraisal): array
    {
        $appraisal->load([
            'user:id,name,email',
            'snapshotCategories.questions',
            'answers.reviews',
            'reviewers.reviewer:id,name,email',
            'comments.reviewer.reviewer:id,name,email',
        ]);

        $role = $this->resolveAnswerRole($appraisal);

        if (! $role) {
            throw ValidationException::withMessages([
                'appraisal' => 'This appraisal is not available for answering.',
            ]);
        }

        if (! $this->canOpenAnswerForm($appraisal, $role)) {
            throw ValidationException::withMessages([
                'appraisal' => $this->answerWorkflowMessage($role),
            ]);
        }

        $answers = $appraisal->answers->keyBy('appraisal_snapshot_question_id');
        $reviewers = $appraisal->reviewers->sortBy('level')->values();
        $authenticatedReviewer = $this->authenticatedReviewer($appraisal);
        $pendingAcknowledgement = $role === 'assignee'
            ? $this->pendingAcknowledgementReviewer($appraisal)
            : null;
        $pendingComment = $pendingAcknowledgement
            ? $appraisal->comments->firstWhere('appraisal_reviewer_id', $pendingAcknowledgement->id)
            : null;

        return [
            'id' => $appraisal->id,
            'role' => $role,
            'role_label' => $pendingAcknowledgement
                ? 'Acknowledgement • Reviewer Level '.$pendingAcknowledgement->level
                : ($role === 'reviewer'
                    ? 'Reviewer Level '.($authenticatedReviewer?->level ?? '')
                    : str($role)->headline()->toString()),
            'is_submitted' => $this->isRoleSubmitted($appraisal, $role),
            'current_reviewer_id' => $authenticatedReviewer?->id,
            'acknowledgement' => $pendingAcknowledgement ? [
                'required' => true,
                'appraisal_reviewer_id' => $pendingAcknowledgement->id,
                'reviewer_name' => $pendingAcknowledgement->reviewer?->name,
                'level' => $pendingAcknowledgement->level,
                'average_rating' => $pendingAcknowledgement->average_rating,
                'submitted_at' => $pendingAcknowledgement->submitted_at?->format('M d, Y h:i A'),
                'overall_comment' => $pendingComment?->comment,
            ] : [
                'required' => false,
            ],
            'period' => Carbon::createFromDate($appraisal->year, $appraisal->month, 1)->format('F Y'),
            'assignee' => [
                'id' => $appraisal->user?->id,
                'name' => $appraisal->user?->name,
                'email' => $appraisal->user?->email,
            ],
            'kpi_name' => $appraisal->kpi_name,
            'kpi_description' => $appraisal->kpi_description,
            'status' => $appraisal->status,
            'current_stage' => $appraisal->current_stage,
            'categories' => $appraisal->snapshotCategories
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'sort_order' => $category->sort_order,
                    'questions' => $category->questions
                        ->map(function ($question) use ($answers, $reviewers, $authenticatedReviewer) {
                            $answer = $answers->get($question->id);
                            $answerReviews = $answer?->reviews?->keyBy('appraisal_reviewer_id') ?? collect();

                            return [
                                'id' => $question->id,
                                'question' => $question->question,
                                'question_type' => $question->question_type,
                                'measurement_type' => $question->measurement_type,
                                'target_value' => $question->target_value,
                                'unit' => $question->unit,
                                'sort_order' => $question->sort_order,
                                'answer' => [
                                    'rating' => $answer?->rating,
                                    'remark' => $answer?->remark,
                                    'answer' => $answer?->answer,
                                    'achieved_value' => $answer?->achieved_value,
                                    'achievement_percentage' => $answer?->achievement_percentage,
                                    'submitted_at' => $answer?->submitted_at?->toISOString(),
                                ],
                                'reviews' => $reviewers->map(function ($reviewer) use ($answerReviews, $authenticatedReviewer) {
                                    $review = $answerReviews->get($reviewer->id);

                                    return [
                                        'appraisal_reviewer_id' => $reviewer->id,
                                        'reviewer_user_id' => $reviewer->reviewer_user_id,
                                        'name' => $reviewer->reviewer?->name,
                                        'role' => $reviewer->role,
                                        'level' => $reviewer->level,
                                        'rating' => $review?->rating,
                                        'remark' => $review?->remark,
                                        'submitted_at' => $review?->submitted_at?->toISOString(),
                                        'is_current' => (int) $authenticatedReviewer?->id === (int) $reviewer->id,
                                        'is_submitted' => filled($reviewer->submitted_at),
                                    ];
                                })->all(),
                            ];
                        })
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'reviewers' => $reviewers->map(fn ($reviewer) => [
                'id' => $reviewer->id,
                'reviewer_user_id' => $reviewer->reviewer_user_id,
                'name' => $reviewer->reviewer?->name,
                'role' => $reviewer->role,
                'level' => $reviewer->level,
                'submitted_at' => $reviewer->submitted_at?->toISOString(),
                'acknowledged_at' => $reviewer->acknowledged_at?->toISOString(),
                'acknowledgement_remark' => $reviewer->acknowledgement_remark,
            ])->all(),
            'comments' => $appraisal->comments->map(fn ($c) => [
                'appraisal_reviewer_id' => $c->appraisal_reviewer_id,
                'level' => $c->reviewer?->level,
                'comment' => $c->comment,
                'commentator_name' => $c->reviewer?->reviewer?->name,
                'created_at' => $c->created_at?->format('M d, Y h:i A'),
            ])->values()->all(),
        ];
    }

    public function saveDraft(Appraisal $appraisal, array $answersData, ?string $overallComment = null): array
    {
        $appraisal->load([
            'user:id,name,email',
            'snapshotCategories.questions',
            'answers.reviews',
            'reviewers',
        ]);

        $role = $this->resolveAnswerRole($appraisal);
        if (! $role) {
            throw ValidationException::withMessages([
                'appraisal' => 'This appraisal is not available for answering.',
            ]);
        }

        if (! $this->canOpenAnswerForm($appraisal, $role)) {
            throw ValidationException::withMessages([
                'appraisal' => $this->answerWorkflowMessage($role),
            ]);
        }

        $this->validateReviewEditable($appraisal, $role);

        $snapshotQuestions = $appraisal->snapshotCategories
            ->flatMap(fn ($category) => $category->questions)
            ->keyBy('id');

        DB::transaction(function () use ($appraisal, $answersData, $role, $snapshotQuestions, $overallComment) {
            foreach ($answersData as $data) {
                $qId = $data['question_id'];
                $questionModel = $snapshotQuestions->get($qId);
                if (! $questionModel) {
                    continue;
                }

                $answer = AppraisalAnswer::firstOrNew([
                    'appraisal_id' => $appraisal->id,
                    'appraisal_snapshot_question_id' => $qId,
                ]);

                $answer->save();
                $this->fillAnswerResponse($answer, $questionModel, $data, $role);
            }

            $this->persistReviewerComment($appraisal, $role, $overallComment);
            $this->updateAppraisalAverageRatings($appraisal);
        });

        return [
            'my_appraisals' => $this->getMyAppraisals($appraisal->month, $appraisal->year),
        ];
    }

    public function submitAnswers(Appraisal $appraisal, array $answersData, ?string $overallComment = null): array
    {
        $appraisal->load([
            'user:id,name,email',
            'snapshotCategories.questions',
            'answers.reviews',
            'reviewers',
        ]);

        $role = $this->resolveAnswerRole($appraisal);
        if (! $role) {
            throw ValidationException::withMessages([
                'appraisal' => 'This appraisal is not available for answering.',
            ]);
        }

        if (! $this->canOpenAnswerForm($appraisal, $role)) {
            throw ValidationException::withMessages([
                'appraisal' => $this->answerWorkflowMessage($role),
            ]);
        }

        $this->validateReviewEditable($appraisal, $role);

        $snapshotQuestions = $appraisal->snapshotCategories
            ->flatMap(fn ($category) => $category->questions)
            ->keyBy('id');

        $submittedAnswers = collect($answersData)->keyBy('question_id');

        $index = 0;
        foreach ($snapshotQuestions as $qId => $questionModel) {
            if (! $submittedAnswers->has($qId)) {
                throw ValidationException::withMessages([
                    'answers' => 'All questions must be answered before submitting.',
                ]);
            }

            $ans = $submittedAnswers->get($qId);
            $questionType = $questionModel->question_type ?? 'rating';

            if ($questionType === AppraisalQuestion::QUESTION_TYPE_RATING) {
                $rating = $ans['rating'] ?? null;

                if ($rating === null || ! is_numeric($rating) || $rating < 0 || $rating > 5.0) {
                    throw ValidationException::withMessages([
                        "answers.{$index}.rating" => 'All ratings must be numeric between 0 and 5.',
                    ]);
                }
                if (strlen(substr(strrchr((string) $rating, '.'), 1)) > 1) {
                    throw ValidationException::withMessages([
                        "answers.{$index}.rating" => 'All ratings must have at most one decimal place.',
                    ]);
                }
            } elseif ($questionType === AppraisalQuestion::QUESTION_TYPE_ANSWER) {
                if ($role === 'assignee') {
                    $assigneeAnswer = $ans['assignee_answer'] ?? null;
                    if ($assigneeAnswer === null || blank(trim((string) $assigneeAnswer))) {
                        throw ValidationException::withMessages([
                            "answers.{$index}.assignee_answer" => 'Answers cannot be empty.',
                        ]);
                    }
                }
            } elseif ($questionType === AppraisalQuestion::QUESTION_TYPE_TARGET) {
                if ($role === 'assignee') {
                    $achievedValue = $ans['achieved_value'] ?? null;

                    if ($achievedValue === null || $achievedValue === '' || ! is_numeric($achievedValue)) {
                        throw ValidationException::withMessages([
                            "answers.{$index}.achieved_value" => 'Achieved value is required.',
                        ]);
                    }
                } elseif ($role === 'reviewer' && blank(trim((string) ($ans['remark'] ?? '')))) {
                    throw ValidationException::withMessages([
                        "answers.{$index}.remark" => 'Remarks cannot be empty.',
                    ]);
                }
            }
            $index++;
        }

        $notificationRecipientId = null;

        DB::transaction(function () use ($appraisal, $submittedAnswers, $role, $snapshotQuestions, $overallComment, &$notificationRecipientId) {
            foreach ($submittedAnswers as $qId => $data) {
                $questionModel = $snapshotQuestions->get($qId);
                if (! $questionModel) {
                    continue;
                }

                $answer = AppraisalAnswer::firstOrNew([
                    'appraisal_id' => $appraisal->id,
                    'appraisal_snapshot_question_id' => $qId,
                ]);

                $answer->save();
                $this->fillAnswerResponse($answer, $questionModel, $data, $role, now());
            }

            $this->persistReviewerComment($appraisal, $role, $overallComment);

            $now = now();
            if ($role === 'assignee') {
                $nextReviewer = $appraisal->reviewers->sortBy('level')->first();
                $notificationRecipientId = (int) ($nextReviewer?->reviewer_user_id ?? 0);
            } elseif ($role === 'reviewer') {
                $reviewer = $this->authenticatedReviewer($appraisal);
                $reviewer?->update(['submitted_at' => $now]);
                $appraisal->current_stage = 'acknowledgement';
            }

            $appraisal->save();

            // Calculate and update average ratings
            $this->updateAppraisalAverageRatings($appraisal);
        });

        if ($notificationRecipientId) {
            $this->notificationService->notifyAppraisalSubmitted(
                $appraisal->fresh(),
                auth()->user(),
                $notificationRecipientId,
            );
        }

        return [
            'my_appraisals' => $this->getMyAppraisals($appraisal->month, $appraisal->year),
        ];
    }

    public function acknowledgeReview(
        Appraisal $appraisal,
        int $appraisalReviewerId,
        ?string $acknowledgementRemark = null,
    ): array {
        if ((int) $appraisal->user_id !== (int) auth()->id()) {
            throw ValidationException::withMessages([
                'appraisal' => 'Only the appraisal assignee can acknowledge a reviewer submission.',
            ]);
        }

        DB::transaction(function () use ($appraisal, $appraisalReviewerId, $acknowledgementRemark) {
            $reviewer = AppraisalReviewer::query()
                ->where('appraisal_id', $appraisal->id)
                ->whereKey($appraisalReviewerId)
                ->lockForUpdate()
                ->first();

            if (! $reviewer || blank($reviewer->submitted_at)) {
                throw ValidationException::withMessages([
                    'appraisal_reviewer_id' => 'This reviewer has not submitted their review.',
                ]);
            }

            if (filled($reviewer->acknowledged_at)) {
                throw ValidationException::withMessages([
                    'appraisal_reviewer_id' => 'This reviewer submission has already been acknowledged.',
                ]);
            }

            $hasUnacknowledgedPreviousStage = AppraisalReviewer::query()
                ->where('appraisal_id', $appraisal->id)
                ->where('level', '<', $reviewer->level)
                ->whereNull('acknowledged_at')
                ->exists();

            if ($hasUnacknowledgedPreviousStage) {
                throw ValidationException::withMessages([
                    'appraisal_reviewer_id' => 'A previous reviewer submission must be acknowledged first.',
                ]);
            }

            $pendingReviewerId = AppraisalReviewer::query()
                ->where('appraisal_id', $appraisal->id)
                ->whereNotNull('submitted_at')
                ->whereNull('acknowledged_at')
                ->orderBy('level')
                ->value('id');

            if ((int) $pendingReviewerId !== (int) $reviewer->id) {
                throw ValidationException::withMessages([
                    'appraisal_reviewer_id' => 'A previous reviewer submission must be acknowledged first.',
                ]);
            }

            $reviewer->update([
                'acknowledged_at' => now(),
                'acknowledgement_remark' => $this->nullableTrim($acknowledgementRemark),
            ]);

            $nextReviewer = AppraisalReviewer::query()
                ->where('appraisal_id', $appraisal->id)
                ->where('level', '>', $reviewer->level)
                ->orderBy('level')
                ->first();

            if ($nextReviewer) {
                $appraisal->update([
                    'current_stage' => filled($nextReviewer->submitted_at)
                        ? 'acknowledgement'
                        : 'reviewer_level_'.$nextReviewer->level,
                ]);

                return;
            }

            $appraisal->update([
                'status' => Appraisal::STATUS_COMPLETED,
                'completed_at' => now(),
                'current_stage' => Appraisal::STATUS_COMPLETED,
                'final_rating' => $reviewer->average_rating,
            ]);
        });

        return [
            'my_appraisals' => $this->getMyAppraisals($appraisal->month, $appraisal->year),
        ];
    }

    private function ensureAppraisalUserIsAccessible(Appraisal $appraisal, string $action): void
    {
        $isAccessible = User::query()
            ->accessibleBy(auth()->user())
            ->whereKey($appraisal->user_id)
            ->exists();

        if (! $isAccessible) {
            throw ValidationException::withMessages([
                'appraisal' => "This appraisal is not available for {$action}.",
            ]);
        }
    }

    private function formatDateTime($dateTime): ?string
    {
        return $dateTime ? \App\Providers\AppServiceProvider::formatAppDateTime($dateTime) : null;
    }

    private function formatDate($date): ?string
    {
        return $date ? \App\Providers\AppServiceProvider::formatAppDate($date) : null;
    }

    private function getCurrentStageLabel(?Appraisal $appraisal, $assigneeSubmittedAt = null): ?string
    {
        if (! $appraisal) {
            return null;
        }

        if ($appraisal->status === Appraisal::STATUS_COMPLETED) {
            return 'Completed';
        }

        if (blank($appraisal->kpi_agreed_at)) {
            return 'KPI Agreement';
        }

        if (blank($assigneeSubmittedAt)) {
            return 'Assignee';
        }

        if (filled($appraisal->current_stage)) {
            return str($appraisal->current_stage)->replace('_', ' ')->headline()->toString();
        }

        $nextReviewer = $appraisal->reviewers
            ->sortBy('level')
            ->first(fn (AppraisalReviewer $reviewer) => blank($reviewer->submitted_at));

        return $nextReviewer
            ? 'Reviewer Level '.$nextReviewer->level
            : 'Completed';
    }

    public function saveComment(Appraisal $appraisal, string $comment): AppraisalComment
    {
        $role = $this->resolveAnswerRole($appraisal);

        if ($role !== 'reviewer') {
            throw ValidationException::withMessages([
                'comment' => 'Only an assigned reviewer can add overall comments.',
            ]);
        }

        $this->validateReviewEditable($appraisal, $role);

        $commentModel = $this->persistReviewerComment($appraisal, $role, $comment);

        return $commentModel->load('reviewer.reviewer:id,name');
    }

    private function persistReviewerComment(Appraisal $appraisal, string $role, ?string $comment): ?AppraisalComment
    {
        if ($role !== 'reviewer') {
            return null;
        }

        $comment = trim((string) $comment);

        $reviewer = $this->authenticatedReviewer($appraisal);

        if (! $reviewer) {
            return null;
        }

        if ($comment === '') {
            $appraisal->comments()->where('appraisal_reviewer_id', $reviewer->id)->forceDelete();

            return null;
        }

        return $appraisal->comments()->updateOrCreate(
            ['appraisal_reviewer_id' => $reviewer->id],
            ['comment' => $comment]
        );
    }

    private function resolveAnswerRole(Appraisal $appraisal): ?string
    {
        $authId = (int) auth()->id();

        if ((int) $appraisal->user_id === $authId) {
            return 'assignee';
        }

        if ($this->authenticatedReviewer($appraisal)) {
            return 'reviewer';
        }

        if (auth()->user()?->can('appraisal.view')) {
            return 'viewer';
        }

        return null;
    }

    private function isRoleSubmitted(Appraisal $appraisal, string $role): bool
    {
        return match ($role) {
            'assignee' => $this->isAssigneeSubmitted($appraisal),
            'reviewer' => filled($this->authenticatedReviewer($appraisal)?->submitted_at),
            'viewer' => true,
            default => true,
        };
    }

    private function canAgreeToKpi(Appraisal $appraisal): bool
    {
        return (int) $appraisal->user_id === (int) auth()->id()
            && $appraisal->status === Appraisal::STATUS_PUBLISHED
            && blank($appraisal->kpi_agreed_at);
    }

    private function canEditAnswer(Appraisal $appraisal, ?string $role): bool
    {
        return $this->canOpenAnswerForm($appraisal, $role)
            && in_array($role, ['assignee', 'reviewer'], true)
            && ! $this->isRoleSubmitted($appraisal, $role);
    }

    private function appraisalRequiresAction(Appraisal $appraisal): bool
    {
        if ($this->canAgreeToKpi($appraisal)) {
            return true;
        }

        $role = $this->resolveAnswerRole($appraisal);

        if ($role === 'assignee' && $this->pendingAcknowledgementReviewer($appraisal)) {
            return true;
        }

        return $this->canEditAnswer($appraisal, $role);
    }

    private function validateReviewEditable(Appraisal $appraisal, string $role): void
    {
        if ($this->isRoleSubmitted($appraisal, $role)) {
            throw ValidationException::withMessages([
                'appraisal' => 'Your appraisal has already been submitted and can no longer be edited.',
            ]);
        }
    }

    private function canOpenAnswerForm(Appraisal $appraisal, ?string $role): bool
    {
        if (! $role || ! in_array(strtolower((string) $appraisal->status), ['published', 'completed', 'closed'], true)) {
            return false;
        }

        return match ($role) {
            'assignee' => filled($appraisal->kpi_agreed_at),
            'reviewer' => $this->reviewerCanAnswer($appraisal),
            'viewer' => true,
            default => false,
        };
    }

    private function answerWorkflowMessage(string $role): string
    {
        return match ($role) {
            'assignee' => 'Please agree to the KPI before answering this appraisal.',
            'reviewer' => 'The previous appraisal stage must be completed and acknowledged before your review.',
            default => 'This appraisal cannot be answered yet.',
        };
    }

    private function authenticatedReviewer(Appraisal $appraisal): ?AppraisalReviewer
    {
        $appraisal->loadMissing('reviewers');

        return $appraisal->reviewers->first(
            fn (AppraisalReviewer $reviewer) => (int) $reviewer->reviewer_user_id === (int) auth()->id()
        );
    }

    private function isAssigneeSubmitted(Appraisal $appraisal): bool
    {
        $hasLoadedWorkflowData = $appraisal->relationLoaded('snapshotCategories')
            && $appraisal->snapshotCategories->every(fn ($category) => $category->relationLoaded('questions'))
            && $appraisal->relationLoaded('answers');

        if ($hasLoadedWorkflowData) {
            $questionIds = $appraisal->snapshotCategories
                ->flatMap(fn ($category) => $category->questions)
                ->pluck('id');

            return $questionIds->isNotEmpty()
                && $appraisal->answers
                    ->whereIn('appraisal_snapshot_question_id', $questionIds)
                    ->whereNotNull('submitted_at')
                    ->count() === $questionIds->count();
        }

        $questionIds = AppraisalSnapshotQuestion::query()
            ->whereHas('category', fn ($query) => $query->where('appraisal_id', $appraisal->id))
            ->pluck('id');

        return $questionIds->isNotEmpty()
            && AppraisalAnswer::query()
                ->where('appraisal_id', $appraisal->id)
                ->whereIn('appraisal_snapshot_question_id', $questionIds)
                ->whereNotNull('submitted_at')
                ->count() === $questionIds->count();
    }

    private function reviewerCanAnswer(Appraisal $appraisal): bool
    {
        $reviewer = $this->authenticatedReviewer($appraisal);

        if (! $reviewer || ! $this->isAssigneeSubmitted($appraisal)) {
            return false;
        }

        return $appraisal->reviewers
            ->where('level', '<', $reviewer->level)
            ->every(fn (AppraisalReviewer $previousReviewer) => filled($previousReviewer->acknowledged_at));
    }

    private function pendingAcknowledgementReviewer(Appraisal $appraisal): ?AppraisalReviewer
    {
        $appraisal->loadMissing('reviewers.reviewer');

        return $appraisal->reviewers
            ->whereNotNull('submitted_at')
            ->whereNull('acknowledged_at')
            ->sortBy('level')
            ->first();
    }

    private function fillAnswerResponse(
        AppraisalAnswer $answer,
        $question,
        array $data,
        string $role,
        $submittedAt = null,
    ): void {
        $questionType = $question->question_type ?? AppraisalQuestion::QUESTION_TYPE_RATING;

        if ($role === 'assignee') {
            if ($questionType === AppraisalQuestion::QUESTION_TYPE_RATING) {
                $answer->rating = $data['rating'] ?? null;
                $answer->remark = $this->nullableTrim($data['remark'] ?? null);
            } elseif ($questionType === AppraisalQuestion::QUESTION_TYPE_ANSWER) {
                $answer->answer = $this->nullableTrim($data['assignee_answer'] ?? null);
            } elseif ($questionType === AppraisalQuestion::QUESTION_TYPE_TARGET) {
                $answer->achieved_value = $data['achieved_value'] ?? null;
                $target = (float) ($question->target_value ?? 0);
                $answer->achievement_percentage = $target > 0 && $answer->achieved_value !== null
                    ? round(((float) $answer->achieved_value / $target) * 100, 2)
                    : null;
            }

            if ($submittedAt) {
                $answer->submitted_at = $submittedAt;
            }

            $answer->save();

            return;
        }

        if ($role !== 'reviewer') {
            return;
        }

        $reviewer = $this->authenticatedReviewer($answer->appraisal);

        if (! $reviewer) {
            return;
        }

        $reviewData = [
            'rating' => $questionType === AppraisalQuestion::QUESTION_TYPE_RATING
                ? ($data['rating'] ?? null)
                : null,
            'remark' => in_array($questionType, [
                AppraisalQuestion::QUESTION_TYPE_RATING,
                AppraisalQuestion::QUESTION_TYPE_TARGET,
            ], true) ? $this->nullableTrim($data['remark'] ?? null) : null,
        ];

        if ($submittedAt) {
            $reviewData['submitted_at'] = $submittedAt;
        }

        $answer->reviews()->updateOrCreate(
            ['appraisal_reviewer_id' => $reviewer->id],
            $reviewData,
        );
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function formatAppraisalSnapshot(Appraisal $appraisal): array
    {
        return [
            'id' => $appraisal->id,
            'user' => [
                'id' => $appraisal->user?->id,
                'name' => $appraisal->user?->name,
                'email' => $appraisal->user?->email,
                'department' => $appraisal->user?->details?->department?->name,
                'designation' => $appraisal->user?->details?->designation?->name,
            ],
            'kpi_id' => $this->resolveSnapshotKpiId($appraisal),
            'kpi_name' => $appraisal->kpi_name,
            'kpi_description' => $appraisal->kpi_description,
            'status' => $appraisal->status,
            'status_label' => str($appraisal->status)->headline()->toString(),
            'is_editable' => $appraisal->status === 'draft',
            'published_at' => $appraisal->published_at?->format('M d, Y h:i A'),
            'reviewer_assignment' => $this->formatReviewerAssignment($appraisal),
            'categories' => $appraisal->snapshotCategories
                ->map(fn ($category) => [
                    'name' => $category->name,
                    'sort_order' => $category->sort_order,
                    'questions' => $category->questions
                        ->map(fn ($question) => [
                            'question' => $question->question,
                            'question_type' => $question->question_type ?? AppraisalQuestion::QUESTION_TYPE_RATING,
                            'measurement_type' => $question->measurement_type,
                            'target_value' => $question->target_value,
                            'unit' => $question->unit,
                            'unit_name' => $question->unit,
                            'sort_order' => $question->sort_order,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function resolveSnapshotKpiId(Appraisal $appraisal): ?int
    {
        if (filled($appraisal->kpi_id)) {
            return (int) $appraisal->kpi_id;
        }

        if (! filled($appraisal->kpi_name)) {
            return null;
        }

        $query = Kpi::query()
            ->active()
            ->where('name', $appraisal->kpi_name);

        $exactMatch = (clone $query)
            ->where('description', $appraisal->kpi_description)
            ->value('id');

        return $exactMatch ?: $query->value('id');
    }

    private function getReviewerAssignmentData($userIds, int $month, int $year): array
    {
        $appraisals = Appraisal::query()
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('user_id', $userIds)
            ->with([
                'user:id,name,email',
                'reviewers.reviewer:id,name,email',
            ])
            ->get()
            ->keyBy('user_id');

        return collect($userIds)
            ->map(fn ($userId) => $appraisals->get((int) $userId))
            ->filter()
            ->map(fn (Appraisal $appraisal) => $this->formatReviewerAssignment($appraisal))
            ->values()
            ->all();
    }

    private function formatReviewerAssignment(Appraisal $appraisal): array
    {
        $chainIds = $this->getReviewerChainUserIds((int) $appraisal->user_id);
        $chainUsers = User::query()
            ->whereIn('id', $chainIds)
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        return [
            'appraisal_id' => $appraisal->id,
            'user' => [
                'id' => $appraisal->user?->id,
                'name' => $appraisal->user?->name,
                'email' => $appraisal->user?->email,
            ],
            'available_reviewers' => $chainIds
                ->map(fn ($id) => $chainUsers->get($id))
                ->filter()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])
                ->values()
                ->all(),
            'reviewers' => $appraisal->reviewers
                ->sortBy('level')
                ->map(fn (AppraisalReviewer $reviewer) => [
                    'id' => $reviewer->id,
                    'reviewer_user_id' => $reviewer->reviewer_user_id,
                    'role' => $reviewer->role,
                    'level' => $reviewer->level,
                    'name' => $reviewer->reviewer?->name,
                    'email' => $reviewer->reviewer?->email,
                ])
                ->values()
                ->all(),
        ];
    }

    private function getReviewerChainUserIds(int $userId)
    {
        $chainIds = collect(User::getReporterChainUserIds($userId))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $existingIds = User::query()
            ->whereIn('id', $chainIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        return $chainIds
            ->filter(fn ($id) => $existingIds->has($id))
            ->values();
    }

    private function validateAssignableUsers($userIds, int $month, int $year): void
    {
        $accessibleUserIds = User::query()
            ->accessibleBy(auth()->user())
            ->whereIn('id', $userIds)
            ->pluck('id');

        if ($accessibleUserIds->count() !== $userIds->count()) {
            throw ValidationException::withMessages([
                'user_ids' => 'One or more selected users are not available for assignment.',
            ]);
        }

        $lockedUsers = Appraisal::query()
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('user_id', $userIds)
            ->where('status', '!=', 'draft')
            ->with('user:id,name')
            ->get();

        if ($lockedUsers->isNotEmpty()) {
            throw ValidationException::withMessages([
                'user_ids' => 'Only draft appraisals can be updated: '.$lockedUsers->pluck('user.name')->filter()->join(', '),
            ]);
        }
    }

    private function replaceSnapshot(Appraisal $appraisal, array $categories, bool $forceDeleteExisting = false): void
    {
        if ($forceDeleteExisting) {
            $appraisal->snapshotCategories()
                ->withTrashed()
                ->get()
                ->each(function ($category) {
                    $category->questions()->withTrashed()->forceDelete();
                    $category->forceDelete();
                });
        } else {
            $appraisal->snapshotCategories()->each(function ($category) {
                $category->questions()->delete();
                $category->delete();
            });
        }

        collect($categories)->values()->each(function (array $category, int $categoryIndex) use ($appraisal) {
            $snapshotCategory = $appraisal->snapshotCategories()->create([
                'name' => $category['name'],
                'sort_order' => $categoryIndex + 1,
            ]);

            $snapshotCategory->questions()->createMany(
                collect($category['questions'] ?? [])
                    ->values()
                    ->map(function (array $question, int $questionIndex) {
                        $questionType = $question['question_type'] ?? AppraisalQuestion::QUESTION_TYPE_RATING;
                        $isTarget = $questionType === AppraisalQuestion::QUESTION_TYPE_TARGET;

                        return [
                            'question' => $question['question'],
                            'question_type' => $questionType,
                            'measurement_type' => $isTarget ? ($question['measurement_type'] ?? null) : null,
                            'target_value' => $isTarget ? ($question['target_value'] ?? null) : null,
                            'unit' => $isTarget ? ($question['unit'] ?? null) : null,
                            'sort_order' => $questionIndex + 1,
                        ];
                    })
                    ->all()
            );
        });
    }

    private function getActiveKpis(): array
    {
        return Kpi::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'description'])
            ->map(fn (Kpi $kpi) => [
                'id' => $kpi->id,
                'name' => $kpi->name,
                'description' => $kpi->description,
            ])
            ->values()
            ->all();
    }

    private function getActiveCategories(): array
    {
        return AppraisalCategory::query()
            ->active()
            ->with(['questions' => fn ($query) => $query->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (AppraisalCategory $category) => [
                'name' => $category->name,
                'sort_order' => $category->sort_order,
                'is_default' => $category->is_default,
                'questions' => $category->questions
                    ->map(fn ($question) => [
                        'question' => $question->question,
                        'question_type' => $question->question_type,
                        'measurement_type' => $question->measurement_type,
                        'target_value' => $question->target_value,
                        'unit' => $question->unit,
                        'sort_order' => $question->sort_order,
                    ])
                    ->values()
                    ->all(),
            ])
            ->filter(fn ($category) => count($category['questions']) > 0)
            ->values()
            ->all();
    }

    private function getAssignmentQuestionOptions(): array
    {
        return [
            'question_types' => AppraisalQuestion::QUESTION_TYPES,
            'default_question_type' => AppraisalQuestion::QUESTION_TYPE_RATING,
            'target_question_type' => AppraisalQuestion::QUESTION_TYPE_TARGET,
            'measurement_types' => AppraisalQuestion::MEASUREMENT_TYPES,
            'question_units' => AppraisalQuestionUnit::query()
                ->active()
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all(),
        ];
    }

    private function getMonths(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn ($month) => [$month => Carbon::create(null, $month, 1)->format('F')])
            ->all();
    }

    private function getYears(int $selectedYear): array
    {
        $start = now()->year - 2;
        $end = now()->year + 1;

        return collect(range(min($start, $selectedYear), max($end, $selectedYear)))
            ->mapWithKeys(fn ($year) => [$year => $year])
            ->all();
    }

    protected function getAccessibleUserIds(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return User::query()
            ->accessibleBy($user)
            ->pluck('users.id')
            ->push($user->id)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function getFilterUsers(Request $request): \Illuminate\Support\Collection
    {
        $userIds = $this->getAccessibleUserIds(auth()->user());

        return User::query()
            ->whereIn('id', $userIds)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getFilterDepartments(): \Illuminate\Support\Collection
    {
        return Department::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getMyAppraisalsPaginator(Request $request, int $month, int $year, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $usersQuery = $this->getMyAppraisalUsersQuery($request, $month, $year)
            ->with(['details.department', 'details.designation', 'primaryAttachment'])
            ->orderBy('name');

        $currentPage = (int) request('my_page', 1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        $total = $usersQuery->count();
        $lastPage = (int) ceil($total / $perPage);
        if ($lastPage < 1) {
            $lastPage = 1;
        }
        if ($currentPage > $lastPage) {
            $currentPage = 1;
        }

        $paginator = $usersQuery->paginate($perPage, ['*'], 'my_page', $currentPage)->withQueryString();

        $appraisals = Appraisal::query()
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('user_id', $paginator->pluck('id'))
            ->withCount(['snapshotQuestions', 'snapshotCategories'])
            ->with([
                'user.details',
                'answers:id,appraisal_id,rating,submitted_at',
                'reviewers.reviewer:id,name',
            ])
            ->get()
            ->keyBy('user_id');

        $paginator->through(function (User $user) use ($appraisals) {
            $appraisal = $appraisals->get($user->id);
            $answerRole = $appraisal ? $this->resolveAnswerRole($appraisal) : null;
            $canAnswer = $appraisal ? $this->canOpenAnswerForm($appraisal, $answerRole) : false;
            $reporter = $appraisal?->reviewers->firstWhere('level', 1);
            $manager = $appraisal?->reviewers->firstWhere('level', 2);
            $assigneeSubmittedAt = $appraisal?->answers
                ->pluck('submitted_at')
                ->filter()
                ->max();

            return [
                'is_assignee' => (int) $user->id === (int) auth()->id(),
                'is_assigned' => (bool) $appraisal,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_image_url' => $user->profile_image_url,
                    'department' => $user->details?->department?->name,
                    'designation' => $user->details?->designation?->name,
                    'avatar_html' => \Illuminate\Support\Facades\Blade::render('<x-user-avatar :user="$user" size="md" />', ['user' => $user]),
                ],
                'appraisal_id' => $appraisal?->id,
                'kpi_name' => $appraisal?->kpi_name,
                'kpi_description' => $appraisal?->kpi_description,
                'questions_count' => $appraisal?->snapshot_questions_count ?? 0,
                'categories_count' => $appraisal?->snapshot_categories_count ?? 0,
                'current_stage' => $appraisal?->current_stage,
                'current_stage_label' => $this->getCurrentStageLabel($appraisal, $assigneeSubmittedAt),
                'status' => $appraisal?->status,
                'status_label' => $appraisal ? str($appraisal->status)->headline()->toString() : 'Not Assigned',
                'completed_at' => $this->formatDate($appraisal?->completed_at),
                'final_rating' => $appraisal?->final_rating,
                'assignee_submitted_at' => $this->formatDateTime($assigneeSubmittedAt),
                'reporter_submitted_at' => $this->formatDateTime($reporter?->submitted_at),
                'manager_submitted_at' => $this->formatDateTime($manager?->submitted_at),
                'assignee_average_rating' => $appraisal?->assignee_average_rating,
                'reporter_average_rating' => $reporter?->average_rating,
                'manager_average_rating' => $manager?->average_rating,
                'assignee_submitted_by_id' => $appraisal?->user_id,
                'assignee_submitted_by_name' => $appraisal?->user?->name,
                'reporter_submitted_by_id' => $reporter?->reviewer_user_id,
                'reporter_submitted_by_name' => $reporter?->reviewer?->name,
                'manager_submitted_by_id' => $manager?->reviewer_user_id,
                'manager_submitted_by_name' => $manager?->reviewer?->name,
                'kpi_agreed_at' => $this->formatDate($appraisal?->kpi_agreed_at),
                'kpi_agreed' => filled($appraisal?->kpi_agreed_at),
                'can_agree' => $appraisal && $this->canAgreeToKpi($appraisal),
                'can_answer' => $canAnswer,
                'can_edit_answer' => $appraisal && $this->canEditAnswer($appraisal, $answerRole),
            ];
        });

        return $paginator;
    }

    private function getMyAppraisalSummary(Request $request, int $month, int $year): array
    {
        $userIds = $this->getMyAppraisalUsersQuery($request, $month, $year)->pluck('id');

        $appraisals = Appraisal::query()
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('user_id', $userIds)
            ->with([
                'snapshotCategories.questions',
                'answers',
                'reviewers.reviewer:id,name',
            ])
            ->get();

        return [
            [
                'key' => 'total',
                'label' => 'Total Appraisals',
                'count' => $appraisals->count(),
                'accent' => 'text-success-400 dark:text-success-300',
            ],
            [
                'key' => 'need_action',
                'label' => 'Need Action',
                'count' => $appraisals->filter(fn (Appraisal $appraisal) => $this->appraisalRequiresAction($appraisal))->count(),
                'accent' => 'text-warning-300 dark:text-warning-200',
            ],
            [
                'key' => 'draft',
                'label' => 'Draft',
                'count' => $appraisals->where('status', Appraisal::STATUS_DRAFT)->count(),
                'accent' => 'text-bgray-700 dark:text-bgray-300',
            ],
            [
                'key' => 'completed',
                'label' => 'Completed',
                'count' => $appraisals->where('status', Appraisal::STATUS_COMPLETED)->count(),
                'accent' => 'text-success-500 dark:text-success-300',
            ],
        ];
    }

    private function getMyAppraisalUsersQuery(Request $request, int $month, int $year)
    {
        $userIds = $this->getScopedUserIds($request);
        $userIds = array_values(array_unique(array_merge([auth()->id()], $userIds)));
        $departmentIds = $this->resolveFilterIds($request, ['department_id']);
        $kpiFilter = $request->input('kpi');
        $allowedStatuses = [
            Appraisal::STATUS_DRAFT,
            Appraisal::STATUS_PUBLISHED,
            Appraisal::STATUS_COMPLETED,
        ];
        $statusFilters = collect($request->input('my_status', []))
            ->flatten()
            ->map(fn ($status) => strtolower((string) $status))
            ->intersect($allowedStatuses)
            ->unique()
            ->values()
            ->all();

        return User::query()
            ->active()
            ->whereIn('id', $userIds)
            ->when($departmentIds !== [], function ($query) use ($departmentIds) {
                $query->where(function ($subQuery) use ($departmentIds) {
                    $subQuery->where('id', auth()->id())
                        ->orWhereHas('details', function ($detailsQuery) use ($departmentIds) {
                            $detailsQuery->whereIn('department_id', $departmentIds);
                        });
                });
            })
            ->when($statusFilters !== [], function ($query) use ($statusFilters, $month, $year) {
                $query->whereHas('appraisals', function ($appraisalQuery) use ($statusFilters, $month, $year) {
                    $appraisalQuery->where('month', $month)
                        ->where('year', $year)
                        ->whereIn('status', $statusFilters);
                });
            })
            ->when($kpiFilter === 'agreed', function ($query) use ($month, $year) {
                $query->whereHas('appraisals', function ($appraisalQuery) use ($month, $year) {
                    $appraisalQuery->where('month', $month)
                        ->where('year', $year)
                        ->whereIn('status', [Appraisal::STATUS_PUBLISHED, Appraisal::STATUS_COMPLETED, Appraisal::STATUS_CLOSED])
                        ->whereNotNull('kpi_agreed_at');
                });
            })
            ->when($kpiFilter === 'not_agreed', function ($query) use ($month, $year) {
                $query->where(function ($subQuery) use ($month, $year) {
                    $subQuery->whereDoesntHave('appraisals', function ($appraisalQuery) use ($month, $year) {
                        $appraisalQuery->where('month', $month)
                            ->where('year', $year)
                            ->whereIn('status', [Appraisal::STATUS_PUBLISHED, Appraisal::STATUS_COMPLETED, Appraisal::STATUS_CLOSED]);
                    })->orWhereHas('appraisals', function ($appraisalQuery) use ($month, $year) {
                        $appraisalQuery->where('month', $month)
                            ->where('year', $year)
                            ->whereIn('status', [Appraisal::STATUS_PUBLISHED, Appraisal::STATUS_COMPLETED, Appraisal::STATUS_CLOSED])
                            ->whereNull('kpi_agreed_at');
                    });
                });
            });
    }

    public function getUsersWithAssignmentsPaginator(int $month, int $year, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $userIds = $this->getScopedUserIds(request());
        $departmentIds = $this->resolveFilterIds(request(), ['department_id']);
        $statusFilter = request('status');

        $usersQuery = User::query()
            ->accessibleBy(auth()->user())
            ->whereIn('id', $userIds)
            ->when($departmentIds !== [], function ($q) use ($departmentIds) {
                $q->whereHas('details', function ($detailsQuery) use ($departmentIds) {
                    $detailsQuery->whereIn('department_id', $departmentIds);
                });
            })
            ->when(filled($statusFilter), function ($q) use ($statusFilter, $month, $year) {
                $q->whereHas('appraisals', function ($aq) use ($statusFilter, $month, $year) {
                    $aq->where('month', $month)
                        ->where('year', $year)
                        ->where('status', strtolower($statusFilter));
                });
            })
            ->with(['details.department', 'details.designation', 'primaryAttachment'])
            ->orderBy('name');

        $currentPage = (int) request('assign_page', 1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        $total = $usersQuery->count();
        $lastPage = (int) ceil($total / $perPage);
        if ($lastPage < 1) {
            $lastPage = 1;
        }
        if ($currentPage > $lastPage) {
            $currentPage = 1;
        }

        $paginator = $usersQuery->paginate($perPage, ['*'], 'assign_page', $currentPage)->withQueryString();

        $appraisals = Appraisal::query()
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('user_id', $paginator->pluck('id'))
            ->with([
                'snapshotCategories:id,appraisal_id,name,sort_order',
                'reviewers.reviewer:id,name',
            ])
            ->withCount('snapshotQuestions')
            ->get()
            ->keyBy('user_id');

        $paginator->through(function (User $user) use ($appraisals) {
            $appraisal = $appraisals->get($user->id);
            $snapshotCategoryNames = $appraisal?->snapshotCategories
                ? $appraisal->snapshotCategories->pluck('name')->filter()->values()
                : collect();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image_url' => $user->profile_image_url,
                'department' => $user->details?->department?->name,
                'designation' => $user->details?->designation?->name,
                'is_assigned' => (bool) $appraisal,
                'appraisal_id' => $appraisal?->id,
                'kpi_id' => $appraisal?->kpi_id,
                'kpi_name' => $appraisal?->kpi_name,
                'status' => $appraisal?->status,
                'status_label' => $appraisal ? str($appraisal->status)->headline()->toString() : 'Not Assigned',
                'is_editable' => ! $appraisal || $appraisal->status === 'draft',
                'categories' => $snapshotCategoryNames->all(),
                'questions_count' => $appraisal?->snapshot_questions_count ?? 0,
                'reviewers' => $appraisal
                    ? $appraisal->reviewers
                        ->sortBy('level')
                        ->map(fn (AppraisalReviewer $reviewer) => [
                            'level' => $reviewer->level,
                            'name' => $reviewer->reviewer?->name,
                        ])
                        ->values()
                        ->all()
                    : [],
                'avatar_html' => \Illuminate\Support\Facades\Blade::render('<x-user-avatar :user="$user" size="md" />', ['user' => $user]),
            ];
        });

        return $paginator;
    }

    private function updateAppraisalAverageRatings(Appraisal $appraisal): void
    {
        $appraisal->load(['snapshotCategories.questions', 'answers.reviews', 'reviewers']);

        $ratingQuestionIds = $appraisal->snapshotCategories
            ->flatMap(fn ($category) => $category->questions)
            ->filter(fn ($question) => ($question->question_type ?? 'rating') === 'rating')
            ->pluck('id')
            ->toArray();

        if (empty($ratingQuestionIds)) {
            $appraisal->update([
                'assignee_average_rating' => null,
            ]);

            $appraisal->reviewers->each->update(['average_rating' => null]);

            return;
        }

        $answers = $appraisal->answers->whereIn('appraisal_snapshot_question_id', $ratingQuestionIds);

        // 1. Assignee average
        $assigneeRatings = $answers->pluck('rating')->filter(fn ($r) => $r !== null);
        $assigneeAvg = $assigneeRatings->isNotEmpty() ? round($assigneeRatings->average(), 2) : null;

        $appraisal->reviewers->each(function (AppraisalReviewer $reviewer) use ($answers) {
            $ratings = $answers
                ->flatMap(fn (AppraisalAnswer $answer) => $answer->reviews)
                ->where('appraisal_reviewer_id', $reviewer->id)
                ->pluck('rating')
                ->filter(fn ($rating) => $rating !== null);
            $average = $ratings->isNotEmpty() ? round($ratings->average(), 2) : null;

            $reviewer->update(['average_rating' => $average]);

        });

        $appraisal->update([
            'assignee_average_rating' => $assigneeAvg,
        ]);
    }
}
