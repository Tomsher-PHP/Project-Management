<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectHostingRecordRequest;
use App\Models\Country;
use App\Models\HostingBillingCycle;
use App\Models\HostingEnvironment;
use App\Models\HostingProvider;
use App\Models\HostingService;
use App\Models\HostingServiceStatus;
use App\Models\Project;
use App\Models\ProjectHostingRecord;
use App\Models\ProjectHostingRenewalHistory;
use App\Models\ProjectHostingStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProjectHostingController extends Controller
{
    /**
     * Render the main Hosting tab view for project detail page.
     */
    public function renderHostingTab(Project $project): string
    {
        abort_unless(auth()->user()?->can('project_hosting.view'), Response::HTTP_FORBIDDEN);

        $hostingRecords = $this->getHostingRecordsQuery($project)->get();
        $formOptions = $this->getFormOptions();

        return view('projects.partials.tabs.hosting', compact(
            'project',
            'hostingRecords',
            'formOptions'
        ))->render();
    }

    /**
     * Get hosting records list HTML via AJAX.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        abort_unless(auth()->user()?->can('project_hosting.view'), Response::HTTP_FORBIDDEN);

        $hostingRecords = $this->getHostingRecordsQuery($project)->get();

        return response()->json([
            'success' => true,
            'html' => view('projects.partials.tabs.hosting.list', [
                'project' => $project,
                'hostingRecords' => $hostingRecords,
            ])->render(),
            'count' => $hostingRecords->count(),
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
        $validated['project_id'] = $project->id;
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        unset($validated['last_renewed_date']);

        DB::beginTransaction();

        try {
            $record = ProjectHostingRecord::create($validated);

            ProjectHostingStatusHistory::create([
                'project_hosting_record_id' => $record->id,
                'from_status_id' => null,
                'to_status_id' => $record->hosting_service_status_id,
                'changed_at' => now(),
                'changed_by' => auth()->id(),
                'reason' => 'Initial status',
                'notes' => null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hosting record added successfully.',
                'html' => $this->renderHostingList($project),
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
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
        $validated['updated_by'] = auth()->id();
        unset($validated['last_renewed_date']);

        DB::beginTransaction();

        try {
            /** @var ProjectHostingRecord $lockedRecord */
            $lockedRecord = ProjectHostingRecord::where('id', $hostingRecord->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatusId = (int) $lockedRecord->hosting_service_status_id;
            $newStatusId = isset($validated['hosting_service_status_id']) ? (int) $validated['hosting_service_status_id'] : null;

            $lockedRecord->update($validated);

            if ($newStatusId !== null && $oldStatusId !== $newStatusId) {
                ProjectHostingStatusHistory::create([
                    'project_hosting_record_id' => $lockedRecord->id,
                    'from_status_id' => $oldStatusId,
                    'to_status_id' => $newStatusId,
                    'changed_at' => now(),
                    'changed_by' => auth()->id(),
                    'reason' => 'Status changed',
                    'notes' => null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hosting record updated successfully.',
                'html' => $this->renderHostingList($project),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update hosting record. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a hosting record.
     */
    public function destroy(Request $request, Project $project, ProjectHostingRecord $hostingRecord): JsonResponse
    {
        abort_unless(auth()->user()?->can('project_hosting.delete'), Response::HTTP_FORBIDDEN);
        $this->ensureHostingRecordBelongsToProject($project, $hostingRecord);

        DB::beginTransaction();

        try {
            $hostingRecord->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'success' => true,
                'message' => 'Hosting record deleted successfully.',
                'html' => $this->renderHostingList($project),
                'render_target' => '#project-hosting-history',
                'render_mode' => 'replace_inner',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
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
    protected function renderHostingList(Project $project): string
    {
        $hostingRecords = $this->getHostingRecordsQuery($project)->get();

        return view('projects.partials.tabs.hosting.list', [
            'project' => $project,
            'hostingRecords' => $hostingRecords,
        ])->render();
    }

    /**
     * Query builder for project hosting records.
     */
    protected function getHostingRecordsQuery(Project $project)
    {
        return ProjectHostingRecord::query()
            ->where('project_id', $project->id)
            ->with([
                'hostingProvider',
                'hostingService',
                'hostingServiceStatus',
                'hostingBillingCycle',
                'hostingEnvironment',
                'creator:id,name',
                'updater:id,name',
            ])
            ->latest('id');
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
