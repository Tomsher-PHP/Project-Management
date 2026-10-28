<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectHostingRecordRequest;
use App\Http\Requests\ProjectHostingRenewalRequest;
use App\Models\Country;
use App\Models\HostingBillingCycle;
use App\Models\HostingEnvironment;
use App\Models\HostingProvider;
use App\Models\HostingService;
use App\Models\HostingServiceStatus;
use App\Models\Project;
use App\Models\ProjectHostingRecord;
use App\Services\ProjectHostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectHostingController extends Controller
{
    /**
     * Create a new controller instance with the ProjectHostingService dependency.
     */
    public function __construct(
        protected ?ProjectHostingService $hostingService = null
    ) {
        $this->hostingService = $hostingService ?? app(ProjectHostingService::class);
    }

    /**
     * Render the main Hosting tab view for project detail page.
     */
    public function renderHostingTab(Project $project, ?Request $request = null): string
    {
        abort_unless(auth()->user()?->can('project_hosting.view'), Response::HTTP_FORBIDDEN);

        $request = $request ?? request();
        $hostingRecords = $this->getFilteredHostingRecords($project, $request);
        $summaryMetrics = $this->calculateSummaryMetrics($project);
        $formOptions = $this->getFormOptions();
        $filterOptions = $this->getFilterOptions();

        return view('projects.partials.tabs.hosting', compact(
            'project',
            'hostingRecords',
            'summaryMetrics',
            'formOptions',
            'filterOptions'
        ))->render();
    }

    /**
     * Get hosting records list HTML via AJAX.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        abort_unless(auth()->user()?->can('project_hosting.view'), Response::HTTP_FORBIDDEN);

        $hostingRecords = $this->getFilteredHostingRecords($project, $request);
        $summaryMetrics = $this->calculateSummaryMetrics($project);

        return response()->json([
            'success' => true,
            'html' => view('projects.partials.tabs.hosting.list', [
                'project' => $project,
                'hostingRecords' => $hostingRecords,
                'formOptions' => $this->getFormOptions(),
            ])->render(),
            'count' => $hostingRecords->total(),
            'summary' => $summaryMetrics,
        ]);
    }

    /**
     * Get a single hosting record for viewing or editing.
     */
    public function show(Request $request, Project $project, ProjectHostingRecord $hostingRecord): JsonResponse
    {
        abort_unless(auth()->user()?->can('project_hosting.view'), Response::HTTP_FORBIDDEN);
        $this->ensureHostingRecordBelongsToProject($project, $hostingRecord);

        $hostingRecord->load([
            'hostingProvider',
            'hostingService',
            'hostingServiceStatus',
            'hostingBillingCycle',
            'hostingEnvironment',
            'creator',
            'updater',
        ]);

        if ($request->query('mode') === 'edit') {
            abort_unless(auth()->user()?->can('project_hosting.edit'), Response::HTTP_FORBIDDEN);

            return response()->json([
                'success' => true,
                'record' => [
                    'id' => $hostingRecord->id,
                    'name' => $hostingRecord->name,
                    'hosting_provider_id' => $hostingRecord->hosting_provider_id,
                    'hosting_service_id' => $hostingRecord->hosting_service_id,
                    'hosting_service_status_id' => $hostingRecord->hosting_service_status_id,
                    'hosting_billing_cycle_id' => $hostingRecord->hosting_billing_cycle_id,
                    'hosting_environment_id' => $hostingRecord->hosting_environment_id,
                    'ownership_type' => $hostingRecord->ownership_type,
                    'domain' => $hostingRecord->domain,
                    'reference' => $hostingRecord->reference,
                    'start_date' => $hostingRecord->start_date?->format('Y-m-d'),
                    'expiry_date' => $hostingRecord->expiry_date?->format('Y-m-d'),
                    'next_renewal_date' => $hostingRecord->next_renewal_date?->format('Y-m-d'),
                    'billing_amount' => $hostingRecord->billing_amount,
                    'currency_code' => $hostingRecord->currency_code,
                    'description' => $hostingRecord->description,
                    'notes' => $hostingRecord->notes,
                    'update_url' => route('projects.hosting.update', [$project, $hostingRecord]),
                    'renew_url' => route('projects.hosting.renew', [$project, $hostingRecord]),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'html' => view('projects.partials.tabs.hosting.view-content', [
                'project' => $project,
                'record' => $hostingRecord,
            ])->render(),
            'record' => $hostingRecord,
        ]);
    }

    /**
     * Store a newly created hosting record.
     */
    public function store(ProjectHostingRecordRequest $request, Project $project): JsonResponse
    {
        abort_unless(auth()->user()?->can('project_hosting.create'), Response::HTTP_FORBIDDEN);

        $validated = $request->validated();

        try {
            $this->hostingService->createRecord($project, $validated, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Hosting record added successfully.',
                'html' => $this->renderHostingList($project, $request),
                'summary' => $this->calculateSummaryMetrics($project),
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to save hosting record. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update an existing hosting record.
     */
    public function update(ProjectHostingRecordRequest $request, Project $project, ProjectHostingRecord $hostingRecord): JsonResponse
    {
        abort_unless(auth()->user()?->can('project_hosting.edit'), Response::HTTP_FORBIDDEN);
        $this->ensureHostingRecordBelongsToProject($project, $hostingRecord);

        $validated = $request->validated();

        try {
            $this->hostingService->updateRecord($hostingRecord, $validated, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Hosting record updated successfully.',
                'html' => $this->renderHostingList($project, $request),
                'summary' => $this->calculateSummaryMetrics($project),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update hosting record. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update status for a hosting record via AJAX.
     */
    public function updateStatus(Request $request, Project $project, ProjectHostingRecord $hostingRecord): JsonResponse
    {
        abort_unless(auth()->user()?->can('project_hosting.edit'), Response::HTTP_FORBIDDEN);
        $this->ensureHostingRecordBelongsToProject($project, $hostingRecord);

        $request->validate([
            'hosting_service_status_id' => ['required', 'integer', 'exists:hosting_service_statuses,id'],
        ]);

        $newStatusId = (int) $request->input('hosting_service_status_id');

        try {
            $lockedRecord = $this->hostingService->updateStatus(
                $hostingRecord,
                $newStatusId,
                'Status changed via dropdown',
                null,
                auth()->id()
            );

            $listRequest = Request::create(
                $request->url(),
                'GET',
                $request->query()
            );

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Hosting status updated successfully.',
                'hosting_service_status_id' => $newStatusId,
                'status_name' => $lockedRecord->hostingServiceStatus?->name,
                'html' => $this->renderHostingList($project, $listRequest),
                'summary' => $this->calculateSummaryMetrics($project),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'success' => false,
                'message' => 'Unable to update hosting status. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Process renewal for a hosting record.
     */
    public function renew(ProjectHostingRenewalRequest $request, Project $project, ProjectHostingRecord $hostingRecord): JsonResponse
    {
        abort_unless(auth()->user()?->can('project_hosting.edit'), Response::HTTP_FORBIDDEN);
        $this->ensureHostingRecordBelongsToProject($project, $hostingRecord);

        $validated = $request->validated();

        try {
            $lockedRecord = $this->hostingService->renewRecord($hostingRecord, $validated, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Hosting record renewed successfully.',
                'html' => $this->renderHostingList($project, $request),
                'summary' => $this->calculateSummaryMetrics($project),
                'drawer_html' => view('projects.partials.tabs.hosting.view-content', [
                    'project' => $project,
                    'record' => $lockedRecord,
                ])->render(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process renewal. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get renewal history for a hosting record via AJAX.
     */
    public function renewalHistory(Request $request, Project $project, ProjectHostingRecord $hostingRecord): JsonResponse
    {
        abort_unless(auth()->user()?->can('project_hosting.view'), Response::HTTP_FORBIDDEN);
        $this->ensureHostingRecordBelongsToProject($project, $hostingRecord);

        $renewals = $this->hostingService->getRenewalHistory($hostingRecord);

        return response()->json([
            'success' => true,
            'html' => view('projects.partials.tabs.hosting.renewal-history-modal-content', [
                'project' => $project,
                'record' => $hostingRecord,
                'renewals' => $renewals,
            ])->render(),
            'count' => $renewals->count(),
        ]);
    }

    /**
     * Get status history for a hosting record via AJAX.
     */
    public function statusHistory(Request $request, Project $project, ProjectHostingRecord $hostingRecord): JsonResponse
    {
        abort_unless(auth()->user()?->can('project_hosting.view'), Response::HTTP_FORBIDDEN);
        $this->ensureHostingRecordBelongsToProject($project, $hostingRecord);

        $statuses = $this->hostingService->getStatusHistory($hostingRecord);

        return response()->json([
            'success' => true,
            'html' => view('projects.partials.tabs.hosting.status-history-modal-content', [
                'project' => $project,
                'record' => $hostingRecord,
                'statuses' => $statuses,
            ])->render(),
            'count' => $statuses->count(),
        ]);
    }

    /**
     * Delete a hosting record.
     */
    public function destroy(Request $request, Project $project, ProjectHostingRecord $hostingRecord): JsonResponse
    {
        abort_unless(auth()->user()?->can('project_hosting.delete'), Response::HTTP_FORBIDDEN);
        $this->ensureHostingRecordBelongsToProject($project, $hostingRecord);

        try {
            $this->hostingService->deleteRecord($hostingRecord);

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Hosting record deleted successfully.',
                'html' => $this->renderHostingList($project, $request),
                'summary' => $this->calculateSummaryMetrics($project),
                'render_target' => '#project-hosting-history',
                'render_mode' => 'replace_inner',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'success' => false,
                'message' => 'Unable to delete hosting record. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Render the hosting list HTML partial.
     */
    protected function renderHostingList(Project $project, ?Request $request = null): string
    {
        $request = $request ?? request();
        $hostingRecords = $this->getFilteredHostingRecords($project, $request);

        return view('projects.partials.tabs.hosting.list', [
            'project' => $project,
            'hostingRecords' => $hostingRecords,
            'formOptions' => $this->getFormOptions(),
        ])->render();
    }

    /**
     * Get filtered and paginated hosting records.
     */
    protected function getFilteredHostingRecords(Project $project, Request $request)
    {
        $perPage = max(1, min(100, (int) $request->input('per_page', 10)));

        return $this->hostingService->getFilteredHostingRecords(
            $project,
            $request->all(),
            $perPage
        )->withQueryString();
    }

    /**
     * Calculate 4 summary metrics for the given project.
     */
    protected function calculateSummaryMetrics(Project $project): array
    {
        return $this->hostingService->calculateSummaryMetrics($project);
    }

    /**
     * Get filter dropdown options.
     */
    protected function getFilterOptions(): array
    {
        return [
            'providers' => HostingProvider::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'services' => HostingService::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'statuses' => HostingServiceStatus::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code']),
        ];
    }

    /**
     * Query builder for project hosting records.
     */
    protected function getHostingRecordsQuery(Project $project)
    {
        return $this->hostingService->getHostingRecordsQuery($project);
    }

    /**
     * Get form dropdown options.
     */
    protected function getFormOptions(): array
    {
        $providers = HostingProvider::active()->orderBy('sort_order')->orderBy('name')->get();
        $services = HostingService::active()->orderBy('sort_order')->orderBy('name')->get();
        $statuses = HostingServiceStatus::active()->orderBy('sort_order')->orderBy('name')->get();
        $billingCycles = HostingBillingCycle::active()->orderBy('sort_order')->orderBy('name')->get();
        $environments = HostingEnvironment::active()->orderBy('sort_order')->orderBy('name')->get();

        $defaultCurrency = config('constants.currency', 'AED');
        $currencyOption = null;
        if (!blank($defaultCurrency)) {
            $country = Country::where('currency', $defaultCurrency)->first();
            $currencyOption = [
                'id' => $defaultCurrency,
                'name' => $country && $country->currency_symbol
                    ? "{$defaultCurrency} ({$country->currency_symbol})"
                    : $defaultCurrency,
            ];
        }

        $defaultStatus = $statuses->firstWhere('is_default', true) ?? HostingServiceStatus::where('is_default', true)->first();

        return [
            'providers' => $providers,
            'services' => $services,
            'statuses' => $statuses,
            'defaultStatusId' => $defaultStatus?->id,
            'billingCycles' => $billingCycles,
            'environments' => $environments,
            'currencyOption' => $currencyOption,
            'defaultCurrency' => $defaultCurrency,
            'ownershipTypes' => ProjectHostingRecord::OWNERSHIP_TYPES,
        ];
    }

    /**
     * Ensure the hosting record belongs to the project.
     */
    protected function ensureHostingRecordBelongsToProject(Project $project, ProjectHostingRecord $hostingRecord): void
    {
        abort_unless((int) $hostingRecord->project_id === (int) $project->id, Response::HTTP_NOT_FOUND);
    }
}
