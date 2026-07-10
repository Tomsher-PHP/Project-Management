<?php

namespace App\Services;

use App\Models\Appraisal;
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

        return [
            'month' => $month,
            'year' => $year,
            'months' => $this->getMonths(),
            'years' => $this->getYears($year),
            'assignmentData' => $this->getAssignmentData($month, $year),
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
            ->active()
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
            ];
        })->values()->all();
    }

    public function getMyAppraisals(int $month, int $year): array
    {
        return Appraisal::query()
            ->where('month', $month)
            ->where('year', $year)
            ->where('user_id', auth()->id())
            ->whereIn('status', ['published', 'completed', 'closed'])
            ->with('snapshotCategories.questions')
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (Appraisal $appraisal) => [
                'id' => $appraisal->id,
                'kpi_name' => $appraisal->kpi_name,
                'kpi_description' => $appraisal->kpi_description,
                'status' => $appraisal->status,
                'status_label' => str($appraisal->status)->headline()->toString(),
                'published_at' => $appraisal->published_at?->format('M d, Y h:i A'),
                'categories' => $appraisal->snapshotCategories
                    ->map(fn ($category) => [
                        'name' => $category->name,
                        'questions' => $category->questions
                            ->map(fn ($question) => [
                                'question' => $question->question,
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all(),
            ])
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
}
