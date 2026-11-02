<?php

namespace App\Http\Controllers;

use App\Models\DailyWorkReportDelivery;
use App\Models\DailyWorkReportLog;
use App\Models\DailyWorkReportRecipient;
use App\Models\DailyWorkReportSetting;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DailyWorkReportSettingsController extends Controller
{
    public function index()
    {

        $settings = DailyWorkReportSetting::current();

        $recipients = DailyWorkReportRecipient::query()
            ->orderBy('scope')
            ->orderBy('email')
            ->get();

        $teams = Team::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $reports = DailyWorkReportLog::query()
            ->with('user:id,name,email')
            ->latest('report_date')
            ->latest('id')
            ->limit(50)
            ->get();

        $deliveryCounts = DailyWorkReportDelivery::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
//     return 'Daily Work Reports Settings page is loading successfully.';

        return view('settings.daily-work-reports.index', compact(
            'settings',
            'recipients',
            'teams',
            'users',
            'reports',
            'deliveryCounts'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['sometimes', 'boolean'],
            'frequency' => [
                'required',
                Rule::in(['daily', 'weekdays', 'weekly']),
            ],
            'delivery_time' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
            'reporting_period' => [
                'required',
                Rule::in(['current_day', 'previous_working_day']),
            ],
            'send_zero_activity' => ['sometimes', 'boolean'],
            'include_inactive_users' => ['sometimes', 'boolean'],
            'weekly_day' => ['required', 'integer', 'between:1,7'],
            'report_sections' => ['nullable', 'array'],
            'report_sections.*' => [
                Rule::in([
                    'current_tasks',
                    'daily_activity',
                    'time_logs',
                    'comments',
                    'developer_notes',
                    'deadlines',
                ]),
            ],
        ]);

        $settings = DailyWorkReportSetting::current();

        $settings->update([
            'enabled' => $request->boolean('enabled'),
            'frequency' => $validated['frequency'],
            'delivery_time' => $validated['delivery_time'],
            'timezone' => $validated['timezone'],
            'reporting_period' => $validated['reporting_period'],
            'send_zero_activity' => $request->boolean('send_zero_activity'),
            'include_inactive_users' => $request->boolean('include_inactive_users'),
            'weekly_day' => $validated['weekly_day'],
            'report_sections' => $validated['report_sections'] ?? [],
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Daily work report settings saved.');
    }

    public function storeRecipient(Request $request): RedirectResponse
    {
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

    public function toggleRecipient(
        DailyWorkReportRecipient $recipient
    ): RedirectResponse {
        $recipient->update([
            'is_active' => ! $recipient->is_active,
            'updated_by' => request()->user()->id,
        ]);

        return back()->with('success', 'Recipient status updated.');
    }

    public function destroyRecipient(
        DailyWorkReportRecipient $recipient
    ): RedirectResponse {
        $recipient->delete();

        return back()->with('success', 'Recipient removed.');
    }
}
