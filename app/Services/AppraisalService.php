<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AppraisalCategory;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
        return [
            'users' => $this->getUsersByPeriod($month, $year),
            'categories' => $this->getActiveCategories(),
        ];
    }

    public function getUsersByPeriod(int $month, int $year): array
    {
        $users = User::query()
            ->accessibleBy(auth()->user())
            ->active()
            ->with(['details.department', 'details.designation'])
            ->orderBy('name')
            ->get();

        $appraisals = Appraisal::query()
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('user_id', $users->pluck('id'))
            ->get()
            ->keyBy('user_id');

        return $users->map(function (User $user) use ($appraisals) {
            $appraisal = $appraisals->get($user->id);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->details?->department?->name,
                'designation' => $user->details?->designation?->name,
                'is_assigned' => (bool) $appraisal,
                'status' => $appraisal?->status,
                'is_editable' => ! $appraisal || $appraisal->status === 'draft',
            ];
        })->values()->all();
    }

    public function store(array $data): array
    {
        return $this->saveAssignments($data, 'draft');
    }

    public function publish(array $data): array
    {
        return $this->saveAssignments($data, 'published');
    }

    private function saveAssignments(array $data, string $status): array
    {
        $month = (int) $data['month'];
        $year = (int) $data['year'];
        $userIds = collect($data['user_ids'])->map(fn ($id) => (int) $id)->values();
        $accessibleUserIds = User::query()
            ->accessibleBy(auth()->user())
            ->whereIn('id', $userIds)
            ->pluck('id');

        if ($accessibleUserIds->count() !== $userIds->unique()->count()) {
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
                'user_ids' => 'Published appraisals cannot be edited: ' . $lockedUsers->pluck('user.name')->filter()->join(', '),
            ]);
        }

        $savedCount = DB::transaction(function () use ($data, $status, $month, $year, $userIds) {
            $count = 0;

            $userIds->each(function (int $userId) use ($data, $status, $month, $year, &$count) {
                $appraisal = Appraisal::query()
                    ->firstOrNew([
                        'year' => $year,
                        'month' => $month,
                        'user_id' => $userId,
                    ]);

                if (! $appraisal->exists) {
                    $appraisal->created_by = auth()->id();
                }

                $appraisal->status = $status;
                $appraisal->published_at = $status === 'published' ? now() : null;
                $appraisal->published_by = $status === 'published' ? auth()->id() : null;
                $appraisal->save();

                $this->buildSnapshot($appraisal, $data['categories']);
                $count++;
            });

            return $count;
        });

        return [
            'count' => $savedCount,
            'users' => $this->getUsersByPeriod($month, $year),
        ];
    }

    public function buildSnapshot(Appraisal $appraisal, array $categories): void
    {
        $appraisal->snapshotCategories()->each(function ($category) {
            $category->questions()->delete();
            $category->delete();
        });

        $this->copyCategories($appraisal, $categories);
    }

    private function copyCategories(Appraisal $appraisal, array $categories): void
    {
        collect($categories)->values()->each(function (array $category, int $categoryIndex) use ($appraisal) {
            $snapshotCategory = $appraisal->snapshotCategories()->create([
                'name' => $category['name'],
                'sort_order' => $categoryIndex + 1,
            ]);

            $this->copyQuestions($snapshotCategory, $category['questions'] ?? []);
        });
    }

    private function copyQuestions($snapshotCategory, array $questions): void
    {
        $snapshotCategory->questions()->createMany(
            collect($questions)
                ->values()
                ->map(fn (array $question, int $index) => [
                    'question' => $question['question'],
                    'sort_order' => $index + 1,
                ])
                ->all()
        );
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
