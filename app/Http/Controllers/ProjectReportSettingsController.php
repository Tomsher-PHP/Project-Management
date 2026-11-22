<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectReportRecipientsRequest;
use App\Http\Requests\ProjectReportSettingRequest;
use App\Models\DailyWorkReportRecipient;
use App\Models\DailyWorkReportSetting;
use App\Models\ProjectStatus;
use App\Models\Team;
use App\Models\User;
use App\Services\Reports\ProjectReportService;
use App\Services\Reports\ProjectReportSettingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectReportSettingsController extends Controller
{
    protected string $pageTitle;
    protected ProjectReportSettingService $settingService;
    protected ProjectReportService $reportService;

    public function __construct(
        ProjectReportSettingService $settingService,
        ProjectReportService $reportService
    ) {
        $this->settingService = $settingService;
        $this->reportService = $reportService;
        $this->pageTitle = 'Project Report Settings';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request): View|JsonResponse
    {
        $configurations = $this->settingService->listConfigurations();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $configurations,
            ]);
        }

        $reportDefinitions = config('report-settings.reports', []);
        $reportKey = 'project_report';

        abort_unless(
            isset($reportDefinitions[$reportKey]) &&
                ($reportDefinitions[$reportKey]['available'] ?? true),
            404
        );

        $currentReport = $reportDefinitions[$reportKey];
        $selectedConfiguration = $configurations->first();
        $settings = $selectedConfiguration ?? DailyWorkReportSetting::forReport($reportKey);

        $recipients = $selectedConfiguration
            ? $this->settingService->getRecipients($selectedConfiguration)
            : collect();

        $activeUsers = User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $teams = Team::query()
            ->where('is_active', true)
            ->with('users:id')
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_active']);

        $projectStatuses = ProjectStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color', 'is_completed']);

        $availableColumns = $this->reportService->getColumnLabels();

        return view('settings.report-settings.project-reports.index', compact(
            'settings',
            'configurations',
            'selectedConfiguration',
            'recipients',
            'activeUsers',
            'teams',
            'users',
            'projectStatuses',
            'availableColumns',
            'reportKey',
            'reportDefinitions',
            'currentReport',
        ));
    }

    public function show(Request $request, int|string $projectReportSetting): JsonResponse
    {
        $configuration = $this->settingService->getConfiguration((int) $projectReportSetting);

        return response()->json([
            'success' => true,
            'data' => $configuration,
        ]);
    }

    public function store(ProjectReportSettingRequest $request): JsonResponse|RedirectResponse
    {
        $configuration = $this->settingService->createConfiguration(
            $request->validated(),
            $request->user()?->id
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Project report configuration created successfully.',
                'data' => $configuration,
            ], 201);
        }

        return redirect()
            ->route('settings.project-reports.index')
            ->with('success', 'Project report configuration created successfully.');
    }

    public function update(ProjectReportSettingRequest $request, int|string|null $projectReportSetting = null): JsonResponse|RedirectResponse
    {
        $targetId = $projectReportSetting ?? $request->input('id', 2);
        $setting = $this->settingService->getConfiguration((int) $targetId);

        $configuration = $this->settingService->updateConfiguration(
            $setting,
            $request->validated(),
            $request->user()?->id
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Project report configuration updated successfully.',
                'data' => $configuration,
            ]);
        }

        return back()->with('success', 'Project report settings saved successfully.');
    }

    public function destroy(Request $request, int|string $projectReportSetting): JsonResponse|RedirectResponse
    {
        $this->settingService->deleteConfiguration((int) $projectReportSetting);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Project report configuration deleted successfully.',
            ]);
        }

        return back()->with('success', 'Project report configuration deleted successfully.');
    }

    public function toggleStatus(Request $request, int|string $projectReportSetting): JsonResponse
    {
        $enabled = $request->has('enabled')
            ? $request->boolean('enabled')
            : null;

        $configuration = $this->settingService->toggleEnabled((int) $projectReportSetting, $enabled, $request->user()?->id);

        return response()->json([
            'success' => true,
            'enabled' => (bool) $configuration->enabled,
            'message' => $configuration->enabled ? 'Configuration enabled.' : 'Configuration disabled.',
            'data' => $configuration,
        ]);
    }

    public function recipients(Request $request, int|string $projectReportSetting): JsonResponse
    {
        $configuration = $this->settingService->getConfiguration((int) $projectReportSetting);
        $recipients = $this->settingService->getRecipients($configuration);

        $internalUserIds = $recipients->whereNotNull('user_id')->pluck('user_id')->values()->all();
        $externalRecipients = $recipients->whereNull('user_id')->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'email' => $r->email,
                'is_active' => (bool) $r->is_active,
            ];
        })->values()->all();

        $tableHtml = view('settings.report-settings.project-reports.partials.recipient-table-rows', [
            'recipients' => $recipients,
            'configuration' => $configuration,
            'reportPermission' => 'report_settings.manage',
        ])->render();

        $externalRowsHtml = view('settings.report-settings.project-reports.partials.external-recipient-rows', [
            'externalRecipients' => $recipients->whereNull('user_id')->values(),
        ])->render();

        return response()->json([
            'success' => true,
            'setting' => [
                'id' => $configuration->id,
                'report_name' => $configuration->report_name,
                'setting_key' => $configuration->setting_key,
            ],
            'internal_user_ids' => $internalUserIds,
            'external_recipients' => $externalRecipients,
            'recipients' => $recipients,
            'table_html' => $tableHtml,
            'external_rows_html' => $externalRowsHtml,
            'has_external' => $recipients->whereNull('user_id')->isNotEmpty(),
            'count' => $recipients->count(),
        ]);
    }

    public function syncRecipients(ProjectReportRecipientsRequest $request, int|string $projectReportSetting): JsonResponse|RedirectResponse
    {
        $configuration = $this->settingService->getConfiguration((int) $projectReportSetting);
        $userIds = (array) $request->input('user_ids', []);
        $externalRecipients = (array) $request->input('external_recipients', []);

        $recipients = $this->settingService->syncRecipients(
            $configuration,
            $userIds,
            $externalRecipients,
            $request->user()?->id
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Recipients updated successfully.',
                'data' => $recipients,
            ]);
        }

        return back()->with('success', 'Recipients updated successfully.');
    }

    public function storeRecipient(Request $request): RedirectResponse
    {
        $reportKey = 'project_report';

        $validated = $request->validate([
            'scope' => ['required', Rule::in(['global', 'team', 'user'])],
            'scope_id' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $scope = $validated['scope'];
        $scopeId = $validated['scope_id'] ?? null;

        if ($scope === 'global') {
            $scopeId = null;
        } elseif ($scope === 'team') {
            validator(
                ['scope_id' => $scopeId],
                ['scope_id' => ['required', 'integer', Rule::exists('teams', 'id')]]
            )->validate();
        } else {
            validator(
                ['scope_id' => $scopeId],
                ['scope_id' => ['required', 'integer', Rule::exists('users', 'id')]]
            )->validate();
        }

        DailyWorkReportRecipient::create([
            'report_key' => $reportKey,
            'scope' => $scope,
            'scope_id' => $scopeId,
            'name' => $validated['name'] ?? null,
            'email' => mb_strtolower(trim($validated['email'])),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Recipient added successfully.');
    }

    public function toggleRecipient(Request $request, int|string|DailyWorkReportRecipient $projectReportSetting, ?DailyWorkReportRecipient $recipient = null): JsonResponse
    {
        if ($recipient === null) {
            $targetRecipient = $projectReportSetting instanceof DailyWorkReportRecipient
                ? $projectReportSetting
                : DailyWorkReportRecipient::findOrFail((int) $projectReportSetting);

            abort_unless($targetRecipient->report_key === 'project_report', 404);

            $targetRecipient->update([
                'is_active' => ! $targetRecipient->is_active,
                'updated_by' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => true,
                'is_active' => (bool) $targetRecipient->is_active,
                'message' => $targetRecipient->is_active ? 'Recipient activated.' : 'Recipient deactivated.',
            ]);
        }

        $setting = $this->settingService->getConfiguration((int) $projectReportSetting);
        $updated = $this->settingService->toggleRecipient($setting, $recipient->id, $request->user()?->id);

        return response()->json([
            'success' => true,
            'is_active' => (bool) $updated->is_active,
            'message' => $updated->is_active ? 'Recipient activated.' : 'Recipient deactivated.',
        ]);
    }

    public function destroyRecipient(Request $request, int|string|DailyWorkReportRecipient $projectReportSetting, ?DailyWorkReportRecipient $recipient = null): JsonResponse|RedirectResponse
    {
        if ($recipient === null) {
            $targetRecipient = $projectReportSetting instanceof DailyWorkReportRecipient
                ? $projectReportSetting
                : DailyWorkReportRecipient::findOrFail((int) $projectReportSetting);

            abort_unless($targetRecipient->report_key === 'project_report', 404);
            $targetRecipient->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Recipient removed.',
                ]);
            }

            return back()->with('success', 'Recipient removed.');
        }

        $setting = $this->settingService->getConfiguration((int) $projectReportSetting);
        $this->settingService->deleteRecipient($setting, $recipient->id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Recipient removed.',
            ]);
        }

        return back()->with('success', 'Recipient removed.');
    }
}
