<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\User;
use Illuminate\Support\Carbon;

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
            'users' => $this->getUsersWithAssignments($month, $year),
        ];
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
