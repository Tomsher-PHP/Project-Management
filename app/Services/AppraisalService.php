<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AppraisalAnswer;
use App\Models\AppraisalCategory;
use App\Models\Kpi;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppraisalService
{
    public function index(?int $month = null, ?int $year = null): array
    {
        $month = $month ?: (int) now()->month;
        $year = $year ?: (int) now()->year;

        $perPage = (int) request('per_page', config('constants.per_page_count', 10));

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
                                'sort_order' => $question->sort_order,
                                'answer' => [
                                    'assignee_rating' => $answer?->assignee_rating,
                                    'assignee_remark' => $answer?->assignee_remark,
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

        DB::transaction(function () use ($appraisal, $answersData, $role) {
            foreach ($answersData as $data) {
                $qId = $data['question_id'];
                $rating = $data['rating'] ?? null;
                $remark = $data['remark'] !== null ? trim((string)$data['remark']) : null;

                $answer = AppraisalAnswer::firstOrNew([
                    'appraisal_id' => $appraisal->id,
                    'appraisal_snapshot_question_id' => $qId,
                ]);

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

                $answer->save();
            }
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

        $questionIds = $appraisal->snapshotCategories
            ->flatMap(fn ($category) => $category->questions->pluck('id'))
            ->toArray();

        $submittedAnswers = collect($answersData)->keyBy('question_id');

        foreach ($questionIds as $index => $qId) {
            if (! $submittedAnswers->has($qId)) {
                throw ValidationException::withMessages([
                    'answers' => 'All questions must be answered before submitting.',
                ]);
            }

            $ans = $submittedAnswers->get($qId);
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
        }

        DB::transaction(function () use ($appraisal, $submittedAnswers, $role) {
            foreach ($submittedAnswers as $qId => $data) {
                $answer = AppraisalAnswer::firstOrNew([
                    'appraisal_id' => $appraisal->id,
                    'appraisal_snapshot_question_id' => $qId,
                ]);

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

        return null;
    }

    private function isRoleSubmitted(Appraisal $appraisal, string $role): bool
    {
        return match ($role) {
            'assignee' => filled($appraisal->assignee_submitted_at),
            'reporter' => filled($appraisal->reporter_submitted_at),
            'manager' => filled($appraisal->manager_submitted_at),
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

    public function getMyAppraisalsPaginator(int $month, int $year, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $usersQuery = User::query()
            ->accessibleBy(auth()->user())
            ->active()
            ->with(['details.department', 'details.designation', 'primaryAttachment'])
            ->orderBy('name');

        $paginator = $usersQuery->paginate($perPage, ['*'], 'my_page')->withQueryString();

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
        $usersQuery = User::query()
            ->accessibleBy(auth()->user())
            ->with(['details.department', 'details.designation', 'primaryAttachment'])
            ->orderBy('name');

        $paginator = $usersQuery->paginate($perPage, ['*'], 'assign_page')->withQueryString();

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
}
