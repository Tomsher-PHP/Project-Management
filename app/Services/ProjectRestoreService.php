<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Customer;

class ProjectRestoreService
{
    /**
     * Get paginated soft-deleted projects.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getDeletedProjects($perPage = 10)
    {
        return Project::onlyTrashed()
            ->accessibleBy(auth()->user())
            ->filter(request()->all())
            ->sort(request()->all())
            ->with(['customer', 'addedBy', 'teamLeader'])
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Validate whether a deleted project can be restored.
     *
     * @param Project $project
     * @return array
     */
    public function validateRestore(Project $project): array
    {
        // 1. Customer Check: Block if related customer is deleted/missing
        if ($project->customer_id) {
            $customerExists = Customer::withTrashed()->where('id', $project->customer_id)->exists();
            if (!$customerExists) {
                return [
                    'can_restore' => false,
                    'message' => 'Cannot restore this project because the related customer is deleted or missing. Restore the customer first.',
                ];
            }
        }

        // 2. Project Code uniqueness (since project_code unique constraint exists)
        if ($project->project_code) {
            $codeInUse = Project::where('project_code', $project->project_code)->exists();
            if ($codeInUse) {
                return [
                    'can_restore' => false,
                    'message' => 'Cannot restore this project because another active project already uses the same project code.',
                ];
            }
        }

        return [
            'can_restore' => true,
            'message' => null,
        ];
    }

    /**
     * Bulk restore soft-deleted projects.
     *
     * @param array $projectIds
     * @return array
     */
    public function bulkRestoreProjects(array $projectIds): array
    {
        $projects = Project::onlyTrashed()
            ->whereIn('id', $projectIds)
            ->get();

        if ($projects->isEmpty()) {
            return [
                'selected_count' => 0,
                'restored_count' => 0,
                'failed_count' => 0,
                'failed_details' => [],
            ];
        }

        $restoredCount = 0;
        $failedCount = 0;
        $failedDetails = [];

        foreach ($projects as $project) {
            $validation = $this->validateRestore($project);

            if (!$validation['can_restore']) {
                $failedCount++;
                $failedDetails[] = "Project '{$project->name}': {$validation['message']}";
                continue;
            }

            // Restore ONLY the project
            $project->restore();
            $restoredCount++;
        }

        return [
            'selected_count' => $projects->count(),
            'restored_count' => $restoredCount,
            'failed_count' => $failedCount,
            'failed_details' => $failedDetails,
        ];
    }
}
