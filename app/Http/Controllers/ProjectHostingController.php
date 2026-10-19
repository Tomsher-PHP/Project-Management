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
                'html' => $this->renderHostingList($project, $request),
                'summary' => $this->calculateSummaryMetrics($project),
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
                'html' => $this->renderHostingList($project, $request),
                'summary' => $this->calculateSummaryMetrics($project),
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
     * Process renewal for a hosting record.
     */
    public function renew(ProjectHostingRenewalRequest $request, Project $project, ProjectHostingRecord $hostingRecord): JsonResponse
    {
        abort_unless(auth()->user()?->can('project_hosting.edit'), Response::HTTP_FORBIDDEN);
        $this->ensureHostingRecordBelongsToProject($project, $hostingRecord);

        $validated = $request->validated();

        DB::beginTransaction();

        try {
            /** @var ProjectHostingRecord $lockedRecord */
            $lockedRecord = ProjectHostingRecord::where('id', $hostingRecord->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Capture previous record state for history tracking
            $previousStartDate = $lockedRecord->start_date;
            $previousExpiryDate = $lockedRecord->expiry_date;

            // Determine updated dates
            $renewalDate = $validated['renewal_date'];
            $newExpiryDate = $validated['new_expiry_date'];
            $newStartDate = !empty($validated['new_start_date']) ? $validated['new_start_date'] : $lockedRecord->start_date;

            // Determine updated billing details (preserve existing if not explicitly provided)
            $billingCycleId = array_key_exists('hosting_billing_cycle_id', $validated) && $validated['hosting_billing_cycle_id'] !== null
                ? (int) $validated['hosting_billing_cycle_id']
                : $lockedRecord->hosting_billing_cycle_id;

            $billingAmount = array_key_exists('billing_amount', $validated) && $validated['billing_amount'] !== null && $validated['billing_amount'] !== ''
                ? $validated['billing_amount']
                : $lockedRecord->billing_amount;

            $currencyCode = !empty($validated['currency_code'])
                ? $validated['currency_code']
                : $lockedRecord->currency_code;

            $reference = array_key_exists('reference', $validated) && $validated['reference'] !== null && $validated['reference'] !== ''
                ? $validated['reference']
                : $lockedRecord->reference;

            $notes = $validated['notes'] ?? null;

            // Create Renewal History record
            ProjectHostingRenewalHistory::create([
                'project_hosting_record_id' => $lockedRecord->id,
                'renewal_date' => $renewalDate,
                'previous_start_date' => $previousStartDate,
                'previous_expiry_date' => $previousExpiryDate,
                'new_start_date' => $newStartDate,
                'new_expiry_date' => $newExpiryDate,
                'hosting_billing_cycle_id' => $billingCycleId,
                'billing_amount' => $billingAmount,
                'currency_code' => $currencyCode,
                'reference' => $reference,
                'notes' => $notes,
                'renewed_by' => auth()->id(),
            ]);

            // Update hosting record with new renewal values
            $recordUpdates = [
                'last_renewed_date' => $renewalDate,
                'expiry_date' => $newExpiryDate,
                'next_renewal_date' => $newExpiryDate, // In PMS, next renewal aligns with expiry date
                'hosting_billing_cycle_id' => $billingCycleId,
                'billing_amount' => $billingAmount,
                'currency_code' => $currencyCode,
                'reference' => $reference,
                'updated_by' => auth()->id(),
            ];

            if (!empty($validated['new_start_date'])) {
                $recordUpdates['start_date'] = $validated['new_start_date'];
            }

            $lockedRecord->update($recordUpdates);

            DB::commit();

            // Reload relations for rendering updated drawer view
            $lockedRecord->load([
                'hostingProvider',
                'hostingService',
                'hostingServiceStatus',
                'hostingBillingCycle',
                'hostingEnvironment',
                'creator',
                'updater',
            ]);

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
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process renewal. Please try again.',
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
                'html' => $this->renderHostingList($project, $request),
                'summary' => $this->calculateSummaryMetrics($project),
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
    protected function renderHostingList(Project $project, ?Request $request = null): string
    {
        $request = $request ?? request();
        $hostingRecords = $this->getFilteredHostingRecords($project, $request);

        return view('projects.partials.tabs.hosting.list', [
            'project' => $project,
            'hostingRecords' => $hostingRecords,
        ])->render();
    }

    /**
     * Get filtered and paginated hosting records.
     */
    protected function getFilteredHostingRecords(Project $project, Request $request)
    {
        $query = $this->getHostingRecordsQuery($project);

        // Search by keyword (name, domain, reference)
        if ($request->filled('search')) {
            $searchTerm = trim((string) $request->input('search'));
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('domain', 'like', "%{$searchTerm}%")
                    ->orWhere('reference', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by provider
        if ($request->filled('hosting_provider_id')) {
            $query->where('hosting_provider_id', (int) $request->input('hosting_provider_id'));
        }

        // Filter by service type
        if ($request->filled('hosting_service_id')) {
            $query->where('hosting_service_id', (int) $request->input('hosting_service_id'));
        }

        // Filter by status
        if ($request->filled('hosting_service_status_id')) {
            $query->where('hosting_service_status_id', (int) $request->input('hosting_service_status_id'));
        }

        // Filter by ownership
        if ($request->filled('ownership_type')) {
            $query->where('ownership_type', $request->input('ownership_type'));
        }

        // Filter by expiry classification
        if ($request->filled('expiry_status')) {
            $today = now()->startOfDay()->toDateString();
            $in30Days = now()->addDays(30)->endOfDay()->toDateString();

            match ($request->input('expiry_status')) {
                'expiring_soon' => $query->whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [$today, $in30Days]),
                'expired' => $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', $today),
                'no_expiry' => $query->whereNull('expiry_date'),
                default => null,
            };
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 10)));

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Calculate 4 summary metrics for the given project.
     */
    protected function calculateSummaryMetrics(Project $project): array
    {
        $baseQuery = ProjectHostingRecord::query()->where('project_id', $project->id);

        $today = now()->startOfDay()->toDateString();
        $in30Days = now()->addDays(30)->endOfDay()->toDateString();

        $total = (clone $baseQuery)->count();

        // Active status count (determined from HostingServiceStatus active code/name)
        $activeStatusIds = HostingServiceStatus::where('code', 'active')
            ->orWhere('name', 'Active')
            ->pluck('id');

        $active = (clone $baseQuery)
            ->whereIn('hosting_service_status_id', $activeStatusIds)
            ->count();

        $expiringSoon = (clone $baseQuery)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $in30Days])
            ->count();

        $expired = (clone $baseQuery)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
        ];
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
