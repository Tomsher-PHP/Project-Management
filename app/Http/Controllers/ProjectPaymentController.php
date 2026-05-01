<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectPaymentStatusRequest;
use App\Models\Project;
use App\Services\ProjectPaymentServices;
use App\Services\ProjectServices;
use App\Traits\ProjectHeaderTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectPaymentController extends Controller
{
    use ProjectHeaderTrait;

    protected $projectPaymentService;
    protected $projectServices;

    public function __construct(ProjectPaymentServices $projectPaymentService, ProjectServices $projectServices)
    {
        $this->projectPaymentService = $projectPaymentService;
        $this->projectServices = $projectServices;
    }

    public function addProjectPaymentStatus(ProjectPaymentStatusRequest $request, Project $project): JsonResponse
    {
        $validated = $request->validated();
        if (!$project->is_linear) {
            return response()->json([
                'success' => false,
                'message' => 'Project payment status can not be updated for non-linear flow project.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->projectPaymentService->createPayment($project, $validated);
        $project = $project->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Project payment status updated successfully.',
            'project_header' => $this->renderProjectHeader($project),
            'payments_tab' => $this->renderPaymentsTab($project),
        ], Response::HTTP_OK);
    }

    public function updateProjectPaymentStatus(ProjectPaymentStatusRequest $request, Project $project, \App\Models\ProjectPayment $payment): JsonResponse
    {
        $validated = $request->validated();
        if (!$project->is_linear) {
            return response()->json([
                'success' => false,
                'message' => 'Project payment status can not be updated for non-linear flow project.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($payment->project_id !== $project->id) {
            abort(403, 'Unauthorized access to this payment.');
        }

        $this->projectPaymentService->updatePayment($payment, $validated);
        $project = $project->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Project payment status updated successfully.',
            'project_header' => $this->renderProjectHeader($project),
            'payments_tab' => $this->renderPaymentsTab($project),
        ], Response::HTTP_OK);
    }

    public function renderPaymentsTab(Project $project): string
    {
        $payments = $project->projectPayments()->orderByDesc('added_at')->orderByDesc('id')->get();
        return view('projects.partials.tabs.payments', compact('project', 'payments'))->render();
    }
}
