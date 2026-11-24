<?php

namespace App\Http\Controllers;

use App\Models\DailyWorkReportDelivery;
use App\Models\DailyWorkReportLog;
use App\Models\DailyWorkReportRecipient;
use App\Models\DailyWorkReportSetting;
use App\Models\Team;
use App\Models\User;
use App\Models\UserReportPreference;
use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DailyWorkReportSettingsController extends Controller
{

    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Report Settings';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index(Request $request): View
    {
        $reportDefinitions = config('report-settings.reports', []);
        $reportKey = $request->query('report_key', 'daily_work');

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

        // Include inactive users as well, so administrators can manage
        // preferences for every employee.
        $users = User::query()
            ->with(['reportPreferences' => function ($query) use ($reportKey) {
                $query->where('report_key', $reportKey);
            }])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_active']);

        $reports = DailyWorkReportLog::query()
            ->where('report_key', $reportKey)
            ->with('user:id,name,email')
            ->latest('report_date')
            ->latest('id')
            ->limit(50)
            ->get();

        $deliveryCounts = DailyWorkReportDelivery::query()
            ->whereHas('reportLog', function ($query) use ($reportKey) {
                $query->where('report_key', $reportKey);
            })
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('settings.report-settings.daily-work-reports.index', compact(
            'settings',
            'recipients',
            'teams',
            'users',
            'reports',
            'deliveryCounts',
            'reportKey',
            'reportDefinitions',
            'currentReport',
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $reportDefinitions = config('report-settings.reports', []);

        $validated = $request->validate([
            'report_key' => [
                'required',
                'string',
                Rule::in(array_keys($reportDefinitions)),
            ],
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
            'reporting_period' => [
                'required',
                Rule::in(['current_day', 'previous_working_day']),
            ],
            'send_zero_activity' => ['sometimes', 'boolean'],
            'include_inactive_users' => ['sometimes', 'boolean'],
            'weekly_day' => ['required_if:frequency,weekly', 'nullable', 'integer', 'between:1,7',],
            'report_sections' => ['nullable', 'array'],
        ]);

        $reportKey = $validated['report_key'];

        $allowedSections = array_keys(
            $reportDefinitions[$reportKey]['sections'] ?? []
        );

        $request->validate([
            'report_sections.*' => [
                'required',
                Rule::in($allowedSections),
            ],
        ]);

        $settings = DailyWorkReportSetting::forReport($reportKey);

        $settings->update([
            'enabled' => $request->boolean('enabled'),
            'frequency' => $validated['frequency'],
            'delivery_time' => Carbon::parse($validated['delivery_time'])->format('H:i:s'),
            'timezone' => $validated['timezone'],
            'reporting_period' => $validated['reporting_period'],
            'send_zero_activity' => $request->boolean('send_zero_activity'),
            'include_inactive_users' => $request->boolean('include_inactive_users'),
            'weekly_day' => $validated['weekly_day'],
            'report_sections' => $validated['report_sections'] ?? [],
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Report settings saved successfully.');
    }

    public function storeRecipient(Request $request): RedirectResponse
    {
        $reportKeys = array_keys(config('report-settings.reports', []));

        $validated = $request->validate([
            'report_key' => ['required', Rule::in($reportKeys)],
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
            'report_key' => $validated['report_key'],
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

    public function toggleRecipient(Request $request, DailyWorkReportRecipient $recipient): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'report_key' => [
                'required',
                Rule::in(array_keys(config('report-settings.reports', []))),
            ],
        ]);

        abort_unless(
            $recipient->report_key === $validated['report_key'],
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
        $validated = $request->validate([
            'report_key' => [
                'required',
                Rule::in(array_keys(config('report-settings.reports', []))),
            ],
        ]);

        abort_unless($recipient->report_key === $validated['report_key'], 404);

        $recipient->delete();

        return back()->with('success', 'Recipient removed.');
    }

    public function updateUserPreference(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $reportKeys = array_keys(config('report-settings.reports', []));

        $validated = $request->validate([
            'report_key' => ['required', Rule::in($reportKeys)],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'is_enabled' => ['sometimes', 'required', 'boolean'],
            'receive_individual_report' => ['sometimes', 'required', 'boolean'],
        ]);

        $field = array_key_exists('receive_individual_report', $validated)
            ? 'receive_individual_report'
            : 'is_enabled';

        DB::transaction(function () use ($request, $validated, $field, &$newStatus) {
            $preference = UserReportPreference::firstOrNew([
                'user_id' => $validated['user_id'],
                'report_key' => $validated['report_key'],
            ]);

            // Missing preference means enabled by default.
            $currentStatus = $preference->exists
                ? (bool) $preference->{$field}
                : true;

            $newStatus = array_key_exists($field, $validated)
                ? (bool) $validated[$field]
                : ! $currentStatus;

            $preference->{$field} = $newStatus;
            $preference->updated_by = $request->user()->id;
            $preference->save();

            if ($field === 'receive_individual_report') {
                $this->syncDailyWorkReportSetting(
                    (int) $validated['user_id'],
                    $validated['report_key'],
                    $newStatus
                );
            }
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'field' => $field,
                'is_active' => $newStatus,
                'message' => 'User report preference updated.',
            ]);
        }

        return back()->with('success', 'User report preference updated.');
    }

    public function bulkUpdateUserPreferences(Request $request)
    {
        $validated = $request->validate([
            'report_key' => ['required', 'string'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', 'exists:users,id'],
            'preference_type' => ['required', 'in:consolidated,individual'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        $field = $validated['preference_type'] === 'individual'
            ? 'receive_individual_report'
            : 'is_enabled';

        foreach ($validated['user_ids'] as $userId) {
            UserReportPreference::updateOrCreate(
                [
                    'user_id' => $userId,
                    'report_key' => $validated['report_key'],
                ],
                [
                    $field => $validated['is_enabled'],
                ]
            );
        }

        return back()->with('success', 'Employee preferences updated successfully.');
    }

    /**
     * Synchronize Daily Work Report preference with User Settings.
     */
    private function syncDailyWorkReportSetting(int $userId, string $reportKey, bool $isEnabled): void
    {
        if ($reportKey !== 'daily_work') {
            return;
        }

        UserSetting::updateOrCreate(
            [
                'user_id' => $userId,
                'key' => 'daily_work_report_mail',
            ],
            [
                'value' => $isEnabled ? 1 : 0,
            ]
        );
    }
}
