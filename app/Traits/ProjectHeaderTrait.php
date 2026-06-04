<?php

namespace App\Traits;

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Providers\AppServiceProvider;
use App\Services\ProjectPaymentServices;
use App\Services\ProjectServices;
use Illuminate\Support\Carbon;

trait ProjectHeaderTrait
{
    protected function getProjectHeaderData(Project $project): array
    {
        $project->loadMissing(['customer', 'projectStatus', 'projectStage', 'addedBy', 'parentProject']);

        $timelines = $this->projectServices->getTimelines($project);
        $paymentSummary = $this->projectPaymentService->getPaymentSummary($project);

        $projectPaymentColor = $paymentSummary['color'] ?? '#EF4444';

        $paymentCoverageText = match (true) {
            !empty($paymentSummary['coverage_start_date']) && !empty($paymentSummary['coverage_end_date']) =>
            'Coverage: ' . AppServiceProvider::formatAppDate($paymentSummary['coverage_start_date']) . ' - ' . AppServiceProvider::formatAppDate($paymentSummary['coverage_end_date']),

            !empty($paymentSummary['coverage_end_date']) =>
            'Coverage ends ' . AppServiceProvider::formatAppDate($paymentSummary['coverage_end_date']),

            default => $paymentSummary['description'] ?? 'No payment recorded yet.',
        };

        $paymentMetaText = $paymentSummary['amount'] !== null
            ? number_format((float) $paymentSummary['amount'], 2)
            : (!empty($paymentSummary['paid_date'])
                ? 'Paid on ' . AppServiceProvider::formatAppDate($paymentSummary['paid_date'])
                : null);

        $statusChangeMinDate = $this->projectServices->getLatestProjectStatusChangeDate($project);
        $stageChangeMinDate = $this->projectServices->getLatestProjectStageChangeDate($project);

        $selectedStatusId = $project->status_id;
        $selectedStageId = $project->project_stage_id;

        return [
            'priority' => config('project_constants.project_priorities')[$project->priority] ?? null,
            'projectTimeline' => $timelines['projectTimeline'],
            'customerTimeline' => $timelines['customerTimeline'],
            'paymentSummary' => $paymentSummary,
            'projectPaymentColor' => $projectPaymentColor,
            'paymentCoverageText' => $paymentCoverageText,
            'paymentMetaText' => $paymentMetaText,
            'projectStatuses' => ProjectStatus::forForm($selectedStatusId, ['order_by' => 'sort_order'])->get(),
            'projectStages' => ProjectStage::forForm($selectedStageId, ['order_by' => 'sort_order'])->get(),
            'statusChangeMinDate' => $statusChangeMinDate?->toDateString(),
            'statusChangeMinDateLabel' => $statusChangeMinDate ? AppServiceProvider::formatAppDate($statusChangeMinDate) : null,
            'stageChangeMinDate' => $stageChangeMinDate?->toDateString(),
            'stageChangeMinDateLabel' => $stageChangeMinDate ? AppServiceProvider::formatAppDate($stageChangeMinDate) : null,
        ];
    }

    protected function renderProjectHeader(Project $project): string
    {
        return view('projects.partials.header', array_merge([
            'project' => $project,
        ], $this->getProjectHeaderData($project)))->render();
    }
}
