<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\ProjectStage;
use App\Models\ProjectStageHistory;
use App\Models\ProjectStatus;
use App\Models\ProjectStatusHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectServices
{
    protected $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['project_flow'] = $data['project_flow'] ?? 'agile';
            $data['priority'] = $data['priority'] ?? 'medium';

            // Handle default start date
            $startDate = $data['start_date'] ?? now(config('constants.timezone'))->toDateString();

            // Handle end date logic
            if (empty($data['end_date'])) {
                $data['end_date'] = Carbon::parse($startDate)->addDays(7)->toDateString();
            }

            // Fresh projects should fall back to the configured default status.
            $data['status_id'] = $data['project_status']
                ?? ProjectStatus::active()->where('is_default', true)->value('id');
            unset($data['project_status']);

            $defaultProjectStageId = ProjectStage::active()->where('is_default', true)->value('id');

            // Create project
            $project = Project::create([
                'project_code' => Project::generateProjectCode(),
                'name' => $data['name'],
                'customer_id' => $data['customer_id'],
                'project_flow' => $data['project_flow'],
                'priority' => $data['priority'],
                'status_id' => $data['status_id'],
                'project_stage_id' => $defaultProjectStageId,
                'start_date' => $startDate,
                'end_date' => $data['end_date'],
            ]);

            // Insert status history
            ProjectStatusHistory::create([
                'project_id' => $project->id,
                'status_id' => $project->status_id,
            ]);

            return $project;
        });
    }

    public function update(Project $project, array $data)
    {
        return DB::transaction(function () use ($project, $data) {
            // Convert minutes -> seconds
            if (array_key_exists('estimated_time_minutes', $data)) {
                $data['estimated_time_seconds'] = $data['estimated_time_minutes'] !== null
                    ? (int) $data['estimated_time_minutes'] * 60
                    : null;
                unset($data['estimated_time_minutes']);
            }

            if (array_key_exists('default_task_estimate_minutes', $data)) {
                $data['default_task_estimate_seconds'] = $data['default_task_estimate_minutes'] !== null
                    ? (int) $data['default_task_estimate_minutes'] * 60
                    : null;
                unset($data['default_task_estimate_minutes']);
            }

            // Default values
            $data['customer_id'] = $data['customer_id'] ?? null;
            $data['default_billable'] = $data['default_billable'] ?? 0;

            // Update project
            $project->update([
                'name' => $data['name'],
                'customer_id' => $data['customer_id'],
                'priority' => $data['priority'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'customer_end_date' => $data['customer_end_date'] ?? null,
                'estimated_time_seconds' => $data['estimated_time_seconds'] ?? null,
                'default_task_estimate_seconds' => $data['default_task_estimate_seconds'] ?? null,
                'domain' => $data['domain'] ?? null,
                'sales_person_id' => $data['sales_person_id'] ?? null,
                'project_category_id' => $data['project_category_id'] ?? null,
                'default_billable' => $data['default_billable'],
            ]);

            // Attach project technologies
            if (isset($data['project_technology_ids'])) {
                $project->technologies()->sync($data['project_technology_ids']);
            }

            return $project->fresh(); // return updated model
        });
    }

    public function updateStatus(Project $project, int $statusId, ?string $statusDate = null, ?string $remarks = null): Project
    {
        return DB::transaction(function () use ($project, $statusId, $statusDate, $remarks) {
            if ((int) $project->status_id !== $statusId) {
                $fromStatusId = $project->status_id;

                $project->update([
                    'status_id' => $statusId,
                ]);

                $historyAddedAt = filled($statusDate)
                    ? Carbon::parse($statusDate, config('constants.timezone'))
                        ->setTimeFrom(now(config('constants.timezone')))
                    : null;

                ProjectStatusHistory::create([
                    'project_id' => $project->id,
                    'from_status_id' => $fromStatusId,
                    'status_id' => $statusId,
                    'added_at' => $historyAddedAt,
                    'remarks' => blank($remarks) ? null : $remarks,
                ]);
            }

            return $project->fresh();
        });
    }

    public function updateStage(Project $project, ?int $projectStageId, ?string $changeDate = null, ?string $remarks = null): Project
    {
        return DB::transaction(function () use ($project, $projectStageId, $changeDate, $remarks) {
            if ((int) ($project->project_stage_id ?? 0) !== (int) ($projectStageId ?? 0)) {
                $fromStageId = $project->project_stage_id;

                $project->update([
                    'project_stage_id' => $projectStageId,
                ]);

                $historyAddedAt = filled($changeDate)
                    ? Carbon::parse($changeDate, config('constants.timezone'))
                        ->setTimeFrom(now(config('constants.timezone')))
                    : null;

                ProjectStageHistory::create([
                    'project_id' => $project->id,
                    'from_stage_id' => $fromStageId,
                    'stage_id' => $projectStageId,
                    'added_at' => $historyAddedAt,
                    'remarks' => blank($remarks) ? null : $remarks,
                ]);
            }

            return $project->fresh();
        });
    }

    public function getTimelines(Project $project): array
    {
        return [
            'projectTimeline' => $this->buildTimeline($project->start_date, $project->end_date),
            'customerTimeline' => $this->buildTimeline($project->start_date, $project->customer_end_date),
        ];
    }

    public function uploadFile(Project $project, array $data, $category = null)
    {
        return DB::transaction(function () use ($project, $data, $category) {
            $attachments = [];
            if (!empty($data['project_files'])) {
                $directory = 'project_files/' . $project->project_code;

                foreach ($data['project_files'] as $file) {
                    $attachments[] = $this->attachmentService->upload(
                        $file,
                        $directory,
                        $project,
                        'public',
                        'public',
                        true,
                        $category
                    );
                }
            }

            return $attachments;
        });
    }

    public function createNote(Project $project, array $data): ProjectNote
    {
        return DB::transaction(function () use ($project, $data) {
            $note = $project->projectNotes()->create([
                'description' => $data['description'],
                'is_active' => true,
            ]);

            if (!empty($data['attachments'])) {
                $directory = 'project_files/' . $project->project_code. '/notes';

                foreach ($data['attachments'] as $file) {
                    $this->attachmentService->upload(
                        $file,
                        $directory,
                        $note,
                        'public',
                        'public',
                        false,
                        'project_note'
                    );
                }
            }

            return $note->load(['attachments', 'addedBy']);
        });
    }

    private function buildTimeline($startDate, $targetDate): array
    {
        $dateFormat = config('constants.date_format');
        $today = now(config('constants.timezone'))->startOfDay();

        if (! $startDate || ! $targetDate) {
            return [
                'percentage' => 0,
                'bar_class' => 'bg-gray-300',
                'text_class' => 'text-bgray-500 dark:text-bgray-300',
                'start_label' => $startDate?->format($dateFormat) ?? '--',
                'end_label' => $targetDate?->format($dateFormat) ?? '--',
            ];
        }

        $start = $startDate->copy()->startOfDay();
        $target = $targetDate->copy()->startOfDay();

        if ($target->lessThanOrEqualTo($start)) {
            return [
                'percentage' => 100,
                'bar_class' => 'bg-red-500',
                'text_class' => 'text-red-500',
                'start_label' => $start->format($dateFormat),
                'end_label' => $target->format($dateFormat),
            ];
        }

        $totalDays = max($start->diffInDays($target), 1);

        if ($today->lessThan($start)) {
            $percentage = 0;
        } elseif ($today->greaterThanOrEqualTo($target)) {
            $percentage = 100;
        } else {
            $percentage = (int) round(($start->diffInDays($today) / $totalDays) * 100);
        }

        if ($percentage <= 25) {
            $barClass = 'bg-success-300';
            $textClass = 'text-success-400';
        } elseif ($percentage <= 50) {
            $barClass = 'bg-yellow-400';
            $textClass = 'text-yellow-500';
        } elseif ($percentage <= 75) {
            $barClass = 'bg-orange-400';
            $textClass = 'text-orange-500';
        } else {
            $barClass = 'bg-red-500';
            $textClass = 'text-red-500';
        }

        return [
            'percentage' => $percentage,
            'bar_class' => $barClass,
            'text_class' => $textClass,
            'start_label' => $start->format($dateFormat),
            'end_label' => $target->format($dateFormat),
        ];
    }
}
