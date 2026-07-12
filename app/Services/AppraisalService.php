<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AppraisalAnswer;
use App\Models\AppraisalComment;
use App\Models\AppraisalCategory;
use App\Models\Kpi;
use App\Models\User;
use App\Models\Team;
use App\Models\Department;
use App\Services\Reports\Concerns\ResolvesTeamUserFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppraisalService
{
    use ResolvesTeamUserFilters;
    public function index(Request $request): array
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

        $myAppraisalsPaginator = $this->getMyAppraisalsPaginator($month, $year, $perPage);
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

        return [
            'month' => $month,
            'year' => $year,
            'months' => $this->getMonths(),
            'years' => $this->getYears($year),
            'assignmentData' => $assignmentData,
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
            ->with('snapshotCategories:id,appraisal_id,name,sort_order')
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
                'kpi_name' => $appraisal?->kpi_name,
                'status' => $appraisal?->status,
                'status_label' => $appraisal ? str($appraisal->status)->headline()->toString() : 'Not Assigned',
                'is_editable' => ! $appraisal || $appraisal->status === 'draft',
                'categories' => $snapshotCategoryNames->all(),
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
            ->with('user.details')
            ->get()
            ->keyBy('user_id');

        return $users
            ->map(function (User $user) use ($appraisals) {
                $appraisal = $appraisals->get($user->id);
                $answerRole = $appraisal ? $this->resolveAnswerRole($appraisal) : null;
                $canAnswer = $appraisal ? $this->canOpenAnswerForm($appraisal, $answerRole) : false;

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
                    'status' => $appraisal?->status,
                    'status_label' => $appraisal ? str($appraisal->status)->headline()->toString() : null,
                    'assignee_submitted_at' => $this->formatDateTime($appraisal?->assignee_submitted_at),
                    'reporter_submitted_at' => $this->formatDateTime($appraisal?->reporter_submitted_at),
                    'manager_submitted_at' => $this->formatDateTime($appraisal?->manager_submitted_at),
                    'kpi_agreed_at' => $this->formatDateTime($appraisal?->kpi_agreed_at),
                    'kpi_agreed' => filled($appraisal?->kpi_agreed_at),
                    'can_agree' => $appraisal
                        && (int) $appraisal->user_id === (int) auth()->id()
                        && strtolower((string) $appraisal->status) === 'published'
                        && blank($appraisal->kpi_agreed_at),
                    'answer_role' => $answerRole,
                    'can_answer' => $canAnswer,
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

        $savedCount = DB::transaction(function () use ($data, $kpi, $status, $month, $year, $userIds) {
            $count = 0;

            $userIds->each(function (int $userId) use ($data, $kpi, $status, $month, $year, &$count) {
                $appraisal = Appraisal::query()
                    ->firstOrNew([
                        'year' => $year,
                        'month' => $month,
                        'user_id' => $userId,
                    ]);

                if (! $appraisal->exists) {
                    $appraisal->created_by = auth()->id();
                }

                $appraisal->kpi_name = $kpi->name;
                $appraisal->kpi_description = $kpi->description;
                $appraisal->status = $status;
                $appraisal->published_at = $status === 'published' ? now() : null;
                $appraisal->published_by = $status === 'published' ? auth()->id() : null;
                $appraisal->save();

                $this->replaceSnapshot($appraisal, $data['categories']);
                $count++;
            });

            return $count;
        });

        return [
            'count' => $savedCount,
            'my_appraisals' => $this->getMyAppraisals($month, $year),
            'users' => $this->getUsersWithAssignments($month, $year),
            'kpis' => $this->getActiveKpis(),
            'categories' => $this->getActiveCategories(),
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

        DB::transaction(function () use ($draftAppraisals) {
            $draftAppraisals->each(function (Appraisal $appraisal) {
                $appraisal->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'published_by' => auth()->id(),
                ]);
            });
        });

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
            $appraisal->load(['user:id,name,email', 'user.details.department', 'user.details.designation', 'snapshotCategories.questions'])
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
            'user.details',
            'snapshotCategories.questions',
            'answers',
            'comments.commentator:id,name',
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

        return [
            'id' => $appraisal->id,
            'role' => $role,
            'role_label' => str($role)->headline()->toString(),
            'is_submitted' => $this->isRoleSubmitted($appraisal, $role),
            'period' => Carbon::createFromDate($appraisal->year, $appraisal->month, 1)->format('F Y'),
            'assignee' => [
                'id' => $appraisal->user?->id,
                'name' => $appraisal->user?->name,
                'email' => $appraisal->user?->email,
            ],
            'kpi_name' => $appraisal->kpi_name,
            'kpi_description' => $appraisal->kpi_description,
            'status' => $appraisal->status,
            'categories' => $appraisal->snapshotCategories
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'sort_order' => $category->sort_order,
                    'questions' => $category->questions
                        ->map(function ($question) use ($answers) {
                            $answer = $answers->get($question->id);

                            return [
                                'id' => $question->id,
                                'question' => $question->question,
                                'question_type' => $question->question_type,
                                'sort_order' => $question->sort_order,
                                'answer' => [
                                    'assignee_rating' => $answer?->assignee_rating,
                                    'assignee_remark' => $answer?->assignee_remark,
                                    'assignee_answer' => $answer?->assignee_answer,
                                    'reporter_rating' => $answer?->reporter_rating,
                                    'reporter_remark' => $answer?->reporter_remark,
                                    'manager_rating' => $answer?->manager_rating,
                                    'manager_remark' => $answer?->manager_remark,
                                ],
                            ];
                        })
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'comments' => $appraisal->comments->map(fn ($c) => [
                'role' => $c->role,
                'comment' => $c->comment,
                'commented_by' => $c->commented_by,
                'commentator_name' => $c->commentator?->name,
                'created_at' => $c->created_at?->format('M d, Y h:i A'),
            ])->values()->all(),
        ];
    }

    public function saveDraft(Appraisal $appraisal, array $answersData): array
    {
        $appraisal->load([
            'user:id,name,email',
            'user.details',
            'snapshotCategories.questions',
            'answers',
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

        DB::transaction(function () use ($appraisal, $answersData, $role, $snapshotQuestions) {
            foreach ($answersData as $data) {
                $qId = $data['question_id'];
                $questionModel = $snapshotQuestions->get($qId);
                if (! $questionModel) {
                    continue;
                }

                $questionType = $questionModel->question_type ?? 'rating';

                $answer = AppraisalAnswer::firstOrNew([
                    'appraisal_id' => $appraisal->id,
                    'appraisal_snapshot_question_id' => $qId,
                ]);

                if ($questionType === 'rating') {
                    $rating = $data['rating'] ?? null;
                    $remark = $data['remark'] !== null ? trim((string)$data['remark']) : null;

                    if ($role === 'assignee') {
                        $answer->assignee_rating = $rating;
                        $answer->assignee_remark = $remark;
                    } elseif ($role === 'reporter') {
                        $answer->reporter_user_id = auth()->id();
                        $answer->reporter_rating = $rating;
                        $answer->reporter_remark = $remark;
                    } elseif ($role === 'manager') {
                        $answer->manager_user_id = auth()->id();
                        $answer->manager_rating = $rating;
                        $answer->manager_remark = $remark;
                    }
                } else {
                    // 'answer' type
                    if ($role === 'assignee') {
                        $answer->assignee_answer = isset($data['assignee_answer']) && $data['assignee_answer'] !== null ? trim((string)$data['assignee_answer']) : null;
                    }
                }

                $answer->save();
            }

            $this->updateAppraisalAverageRatings($appraisal);
        });

        return [
            'my_appraisals' => $this->getMyAppraisals($appraisal->month, $appraisal->year),
        ];
    }

    public function submitAnswers(Appraisal $appraisal, array $answersData): array
    {
        $appraisal->load([
            'user:id,name,email',
            'user.details',
            'snapshotCategories.questions',
            'answers',
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

            if ($questionType === 'rating') {
                $rating = $ans['rating'] ?? null;
                $remark = $ans['remark'] ?? null;

                if ($rating === null || ! is_numeric($rating) || $rating < 0.1 || $rating > 5.0) {
                    throw ValidationException::withMessages([
                        "answers.{$index}.rating" => 'All ratings must be numeric between 0.1 and 5.0.',
                    ]);
                }
                if (strlen(substr(strrchr((string)$rating, "."), 1)) > 1) {
                    throw ValidationException::withMessages([
                        "answers.{$index}.rating" => 'All ratings must have at most one decimal place.',
                    ]);
                }

                if ($remark === null || blank(trim((string)$remark))) {
                    throw ValidationException::withMessages([
                        "answers.{$index}.remark" => 'Remarks cannot be empty.',
                    ]);
                }
            } else {
                // 'answer' type question
                if ($role === 'assignee') {
                    $assigneeAnswer = $ans['assignee_answer'] ?? null;
                    if ($assigneeAnswer === null || blank(trim((string)$assigneeAnswer))) {
                        throw ValidationException::withMessages([
                            "answers.{$index}.assignee_answer" => 'Answers cannot be empty.',
                        ]);
                    }
                }
            }
            $index++;
        }

        DB::transaction(function () use ($appraisal, $submittedAnswers, $role, $snapshotQuestions) {
            foreach ($submittedAnswers as $qId => $data) {
                $questionModel = $snapshotQuestions->get($qId);
                if (! $questionModel) {
                    continue;
                }

                $questionType = $questionModel->question_type ?? 'rating';

                $answer = AppraisalAnswer::firstOrNew([
                    'appraisal_id' => $appraisal->id,
                    'appraisal_snapshot_question_id' => $qId,
                ]);

                if ($questionType === 'rating') {
                    if ($role === 'assignee') {
                        $answer->assignee_rating = $data['rating'];
                        $answer->assignee_remark = trim((string)$data['remark']);
                        if (blank($answer->assignee_submitted_at)) {
                            $answer->assignee_submitted_at = now();
                        }
                    } elseif ($role === 'reporter') {
                        $answer->reporter_user_id = auth()->id();
                        $answer->reporter_rating = $data['rating'];
                        $answer->reporter_remark = trim((string)$data['remark']);
                        if (blank($answer->reporter_submitted_at)) {
                            $answer->reporter_submitted_at = now();
                        }
                    } elseif ($role === 'manager') {
                        $answer->manager_user_id = auth()->id();
                        $answer->manager_rating = $data['rating'];
                        $answer->manager_remark = trim((string)$data['remark']);
                        if (blank($answer->manager_submitted_at)) {
                            $answer->manager_submitted_at = now();
                        }
                    }
                } else {
                    // 'answer' type
                    if ($role === 'assignee') {
                        $answer->assignee_answer = trim((string)$data['assignee_answer']);
                        if (blank($answer->assignee_submitted_at)) {
                            $answer->assignee_submitted_at = now();
                        }
                    }
                }

                $answer->save();
            }

            $now = now();
            if ($role === 'assignee') {
                $appraisal->assignee_submitted_at = $now;
            } elseif ($role === 'reporter') {
                $appraisal->reporter_submitted_at = $now;
            } elseif ($role === 'manager') {
                $appraisal->manager_submitted_at = $now;
                $appraisal->status = 'completed';
                $appraisal->completed_at = $now;
            }

            $appraisal->save();

            // Calculate and update average ratings
            $this->updateAppraisalAverageRatings($appraisal);
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

    public function saveComment(Appraisal $appraisal, string $comment): AppraisalComment
    {
        $role = $this->resolveAnswerRole($appraisal);

        if (! in_array($role, ['reporter', 'manager'], true)) {
            throw ValidationException::withMessages([
                'comment' => 'Only the reporter or manager can add overall comments.',
            ]);
        }

        if ($role === 'reporter' && blank($appraisal->reporter_submitted_at)) {
            throw ValidationException::withMessages([
                'comment' => 'You can only comment after submitting your appraisal answers.',
            ]);
        }

        if ($role === 'manager' && blank($appraisal->manager_submitted_at)) {
            throw ValidationException::withMessages([
                'comment' => 'You can only comment after submitting your appraisal answers.',
            ]);
        }

        $commentModel = $appraisal->comments()->updateOrCreate(
            ['role' => $role],
            [
                'commented_by' => auth()->id(),
                'comment' => $comment,
            ]
        );

        return $commentModel->load('commentator:id,name');
    }

    private function resolveAnswerRole(Appraisal $appraisal): ?string
    {
        $authId = (int) auth()->id();

        if ((int) $appraisal->user_id === $authId) {
            return 'assignee';
        }

        $details = $appraisal->user?->details;

        if ($details && (int) $details->reporter_id === $authId) {
            return 'reporter';
        }

        if ($details && (int) $details->manager_id === $authId) {
            return 'manager';
        }

        if (auth()->user()?->can('appraisal.view')) {
            return 'viewer';
        }

        return null;
    }

    private function isRoleSubmitted(Appraisal $appraisal, string $role): bool
    {
        return match ($role) {
            'assignee' => filled($appraisal->assignee_submitted_at),
            'reporter' => filled($appraisal->reporter_submitted_at),
            'manager' => filled($appraisal->manager_submitted_at),
            'viewer' => true,
            default => true,
        };
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
            'reporter' => filled($appraisal->assignee_submitted_at),
            'manager' => filled($appraisal->reporter_submitted_at),
            'viewer' => true,
            default => false,
        };
    }

    private function answerWorkflowMessage(string $role): string
    {
        return match ($role) {
            'assignee' => 'Please agree to the KPI before answering this appraisal.',
            'reporter' => 'The assignee must submit this appraisal before reporter review.',
            'manager' => 'The reporter must submit this appraisal before manager review.',
            default => 'This appraisal cannot be answered yet.',
        };
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
            'categories' => $appraisal->snapshotCategories
                ->map(fn ($category) => [
                    'name' => $category->name,
                    'sort_order' => $category->sort_order,
                    'questions' => $category->questions
                        ->map(fn ($question) => [
                            'question' => $question->question,
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
                'user_ids' => 'Only draft appraisals can be updated: ' . $lockedUsers->pluck('user.name')->filter()->join(', '),
            ]);
        }
    }

    private function replaceSnapshot(Appraisal $appraisal, array $categories): void
    {
        $appraisal->snapshotCategories()->each(function ($category) {
            $category->questions()->delete();
            $category->delete();
        });

        collect($categories)->values()->each(function (array $category, int $categoryIndex) use ($appraisal) {
            $snapshotCategory = $appraisal->snapshotCategories()->create([
                'name' => $category['name'],
                'sort_order' => $categoryIndex + 1,
            ]);

            $snapshotCategory->questions()->createMany(
                collect($category['questions'] ?? [])
                    ->values()
                    ->map(fn (array $question, int $questionIndex) => [
                        'question' => $question['question'],
                        'question_type' => $question['question_type'] ?? 'rating',
                        'sort_order' => $questionIndex + 1,
                    ])
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
                        'sort_order' => $question->sort_order,
                    ])
                    ->values()
                    ->all(),
            ])
            ->filter(fn ($category) => count($category['questions']) > 0)
            ->values()
            ->all();
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
            ->map(fn($id) => (int) $id)
            ->filter(fn(int $id) => $id > 0)
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

    public function getMyAppraisalsPaginator(int $month, int $year, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $userIds = $this->getScopedUserIds(request());
        $userIds = array_values(array_unique(array_merge([auth()->id()], $userIds)));
        $departmentIds = $this->resolveFilterIds(request(), ['department_id']);
        $kpiFilter = request('kpi');

        $usersQuery = User::query()
            ->active()
            ->whereIn('id', $userIds)
            ->when($departmentIds !== [], function ($q) use ($departmentIds) {
                $q->where(function ($sub) use ($departmentIds) {
                    $sub->where('id', auth()->id())
                        ->orWhereHas('details', function ($detailsQuery) use ($departmentIds) {
                            $detailsQuery->whereIn('department_id', $departmentIds);
                        });
                });
            })
            ->when($kpiFilter === 'agreed', function ($q) use ($month, $year) {
                $q->whereHas('appraisals', function ($aq) use ($month, $year) {
                    $aq->where('month', $month)
                       ->where('year', $year)
                       ->whereIn('status', ['published', 'completed', 'closed'])
                       ->whereNotNull('kpi_agreed_at');
                });
            })
            ->when($kpiFilter === 'not_agreed', function ($q) use ($month, $year) {
                $q->where(function ($sub) use ($month, $year) {
                    $sub->whereDoesntHave('appraisals', function ($aq) use ($month, $year) {
                        $aq->where('month', $month)
                           ->where('year', $year)
                           ->whereIn('status', ['published', 'completed', 'closed']);
                    })->orWhereHas('appraisals', function ($aq) use ($month, $year) {
                        $aq->where('month', $month)
                           ->where('year', $year)
                           ->whereIn('status', ['published', 'completed', 'closed'])
                           ->whereNull('kpi_agreed_at');
                    });
                });
            })
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
            ->whereIn('status', ['published', 'completed', 'closed'])
            ->with('user.details')
            ->get()
            ->keyBy('user_id');

        $paginator->through(function (User $user) use ($appraisals) {
            $appraisal = $appraisals->get($user->id);
            $answerRole = $appraisal ? $this->resolveAnswerRole($appraisal) : null;
            $canAnswer = $appraisal ? $this->canOpenAnswerForm($appraisal, $answerRole) : false;

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
                'status' => $appraisal?->status,
                'status_label' => $appraisal ? str($appraisal->status)->headline()->toString() : null,
                'assignee_submitted_at' => $this->formatDateTime($appraisal?->assignee_submitted_at),
                'reporter_submitted_at' => $this->formatDateTime($appraisal?->reporter_submitted_at),
                'manager_submitted_at' => $this->formatDateTime($appraisal?->manager_submitted_at),
                'kpi_agreed_at' => $this->formatDateTime($appraisal?->kpi_agreed_at),
                'kpi_agreed' => filled($appraisal?->kpi_agreed_at),
                'can_agree' => $appraisal
                    && (int) $appraisal->user_id === (int) auth()->id()
                    && $appraisal->status === 'published'
                    && ! $appraisal->kpi_agreed_at,
                'can_answer' => $canAnswer,
            ];
        });

        return $paginator;
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
            ->with('snapshotCategories:id,appraisal_id,name,sort_order')
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
                'kpi_name' => $appraisal?->kpi_name,
                'status' => $appraisal?->status,
                'status_label' => $appraisal ? str($appraisal->status)->headline()->toString() : 'Not Assigned',
                'is_editable' => ! $appraisal || $appraisal->status === 'draft',
                'categories' => $snapshotCategoryNames->all(),
                'avatar_html' => \Illuminate\Support\Facades\Blade::render('<x-user-avatar :user="$user" size="md" />', ['user' => $user]),
            ];
        });

        return $paginator;
    }

    private function updateAppraisalAverageRatings(Appraisal $appraisal): void
    {
        $appraisal->load(['snapshotCategories.questions', 'answers']);
        
        $ratingQuestionIds = $appraisal->snapshotCategories
            ->flatMap(fn ($category) => $category->questions)
            ->filter(fn ($question) => ($question->question_type ?? 'rating') === 'rating')
            ->pluck('id')
            ->toArray();
            
        if (empty($ratingQuestionIds)) {
            $appraisal->update([
                'assignee_average_rating' => null,
                'reporter_average_rating' => null,
                'manager_average_rating' => null,
            ]);
            return;
        }
        
        $answers = $appraisal->answers->whereIn('appraisal_snapshot_question_id', $ratingQuestionIds);
        
        // 1. Assignee average
        $assigneeRatings = $answers->pluck('assignee_rating')->filter(fn ($r) => $r !== null);
        $assigneeAvg = $assigneeRatings->isNotEmpty() ? round($assigneeRatings->average(), 2) : null;
        
        // 2. Reporter average
        $reporterRatings = $answers->pluck('reporter_rating')->filter(fn ($r) => $r !== null);
        $reporterAvg = $reporterRatings->isNotEmpty() ? round($reporterRatings->average(), 2) : null;
        
        // 3. Manager average
        $managerRatings = $answers->pluck('manager_rating')->filter(fn ($r) => $r !== null);
        $managerAvg = $managerRatings->isNotEmpty() ? round($managerRatings->average(), 2) : null;
        
        $appraisal->update([
            'assignee_average_rating' => $assigneeAvg,
            'reporter_average_rating' => $reporterAvg,
            'manager_average_rating' => $managerAvg,
        ]);
    }
}
