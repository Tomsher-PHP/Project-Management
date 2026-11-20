<?php

namespace App\Http\Controllers;

use App\Models\DailyWorkReportRecipient;
use App\Models\DailyWorkReportSetting;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectReportSettingsController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Project Report Settings';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request): View
    {
        $reportDefinitions = config('report-settings.reports', []);
        $reportKey = 'project_report';

        abort_unless(
            isset($reportDefinitions[$reportKey]) &&
                ($reportDefinitions[$reportKey]['available'] ?? true),
            404
        );

        $currentReport = $reportDefinitions[$reportKey];
        $settings = DailyWorkReportSetting::forReport($reportKey);

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

        return view('settings.report-settings.project-reports.index', compact(
            'settings',
            'recipients',
            'teams',
            'users',
            'reportKey',
            'reportDefinitions',
            'currentReport',
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $reportDefinitions = config('report-settings.reports', []);
        $reportKey = 'project_report';

        abort_unless(
            isset($reportDefinitions[$reportKey]) &&
                ($reportDefinitions[$reportKey]['available'] ?? true),
            404
        );

        $validated = $request->validate([
            'enabled' => ['sometimes', 'boolean'],
            'frequency' => ['required', Rule::in(['daily', 'weekdays', 'weekly'])],
            'delivery_time' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    try {
                        Carbon::parse($value);
                    } catch (\Throwable $e) {
                        $fail('The delivery time is invalid.');
                    }
                },
            ],
            'timezone' => ['required', 'timezone'],
            'weekly_day' => ['required_if:frequency,weekly', 'nullable', 'integer', 'between:1,7'],
        ]);

        $settings = DailyWorkReportSetting::forReport($reportKey);

        $settings->update([
            'enabled' => $request->boolean('enabled'),
            'frequency' => $validated['frequency'],
            'delivery_time' => Carbon::parse($validated['delivery_time'])->format('H:i:s'),
            'timezone' => $validated['timezone'],
            'weekly_day' => $validated['weekly_day'] ?? $settings->weekly_day,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Project report settings saved successfully.');
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
