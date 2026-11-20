<?php

namespace App\Http\Controllers;

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
        $settings = $configurations->first() ?? DailyWorkReportSetting::forReport($reportKey);

        $recipients = DailyWorkReportRecipient::query()
            ->where('report_key', $reportKey)
            ->orderBy('scope')
            ->orderBy('email')
            ->get();

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
            ->get(['id', 'name', 'color']);

        $availableColumns = $this->reportService->getColumnLabels();

        return view('settings.report-settings.project-reports.index', compact(
            'settings',
            'configurations',
            'recipients',
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

    public function toggleRecipient(Request $request, DailyWorkReportRecipient $recipient): JsonResponse
    {
        abort_unless(
            $recipient->report_key === 'project_report',
            404
        );

        $recipient->update([
            'is_active' => ! $recipient->is_active,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => (bool) $recipient->is_active,
        ]);
    }

    public function destroyRecipient(Request $request, DailyWorkReportRecipient $recipient): RedirectResponse
    {
        abort_unless(
            $recipient->report_key === 'project_report',
            404
        );

        $recipient->delete();

        return back()->with('success', 'Recipient removed.');
    }
}
