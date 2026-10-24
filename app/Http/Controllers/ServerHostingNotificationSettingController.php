<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServerHostingNotificationSettingRequest;
use App\Models\ProjectNotificationDefault;
use App\Models\ProjectNotificationSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServerHostingNotificationSettingController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Server & Hosting Settings';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index()
    {
        $setting = ProjectNotificationSetting::with(['days', 'users', 'recipients'])
            ->where('notification_type', ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON)
            ->first();

        if (!$setting) {
            $setting = new ProjectNotificationSetting([
                'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON,
                'is_enabled' => true,
            ]);
            $selectedUsers = [];
            $selectedEmails = '';
            $selectedDays = [30];
        } else {
            $selectedUsers = $setting->users->pluck('id')->toArray();
            $emailsList = $setting->recipients()
                ->whereNotNull('email')
                ->pluck('email')
                ->toArray();
            $selectedEmails = implode("\n", $emailsList);
            $selectedDays = $setting->days->pluck('days_before')->sortDesc()->values()->toArray();
        }

        $users = User::active()->get();

        return view('settings.server-hosting-notifications.index', [
            'setting' => $setting,
            'users' => $users,
            'selectedUsers' => $selectedUsers,
            'selectedEmails' => $selectedEmails,
            'selectedDays' => $selectedDays,
            'currentTab' => 'notifications',
            'createPermission' => 'server_hosting.create',
            'editPermission' => 'server_hosting.edit',
        ]);
    }

    public function store(ServerHostingNotificationSettingRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $setting = ProjectNotificationSetting::create([
                'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON,
                'is_enabled' => $data['is_enabled'],
                'created_by' => auth()->id(),
            ]);

            $this->syncDays($setting, $data['days_before'] ?? []);
            $this->syncRecipients($setting, $data['user_ids'] ?? [], $data['emails'] ?? []);
        });

        return redirect()->route('settings.server-hosting-notifications.index')
            ->with('success', 'Notification settings created successfully.');
    }

    public function update(ServerHostingNotificationSettingRequest $request, ProjectNotificationSetting $server_hosting_notification)
    {
        // Ensure that the setting model being updated belongs to the hosting notification type
        if ($server_hosting_notification->notification_type !== ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON) {
            abort(404);
        }

        $data = $request->validated();

        DB::transaction(function () use ($server_hosting_notification, $data) {
            $server_hosting_notification->update([
                'is_enabled' => $data['is_enabled'],
            ]);

            $this->syncDays($server_hosting_notification, $data['days_before'] ?? []);
            $this->syncRecipients($server_hosting_notification, $data['user_ids'] ?? [], $data['emails'] ?? []);
        });

        return redirect()->route('settings.server-hosting-notifications.index')
            ->with('success', 'Notification settings updated successfully.');
    }

    /**
     * Synchronize reminder intervals in project_notification_setting_days safely within transaction.
     */
    protected function syncDays(ProjectNotificationSetting $setting, array $days): void
    {
        $normalizedDays = [];
        foreach ($days as $day) {
            $intDay = (int) $day;
            if ($intDay >= 0 && !in_array($intDay, $normalizedDays, true)) {
                $normalizedDays[] = $intDay;
            }
        }

        // Fetch existing day rows
        $existingRows = $setting->days()->get();

        // Delete day rows not in submitted list
        foreach ($existingRows as $row) {
            if (!in_array($row->days_before, $normalizedDays, true)) {
                $row->delete();
            }
        }

        $storedDays = $existingRows->pluck('days_before')->toArray();

        // Create new entries
        foreach ($normalizedDays as $day) {
            if (!in_array($day, $storedDays, true)) {
                $setting->days()->create([
                    'days_before' => $day,
                ]);
            }
        }
    }

    /**
     * Synchronize PMS user recipients and external email recipients safely within transaction.
     */
    protected function syncRecipients(ProjectNotificationSetting $setting, array $userIds, array $emails): void
    {
        // 1. Sync PMS users using the existing belongsToMany relationship
        $setting->users()->sync($userIds);

        // 2. Synchronize external email recipients
        // Normalize submitted email list to lowercase trimmed values, unique
        $normalizedEmails = [];
        foreach ($emails as $email) {
            $cleanEmail = strtolower(trim((string) $email));
            if ($cleanEmail !== '' && !in_array($cleanEmail, $normalizedEmails, true)) {
                $normalizedEmails[] = $cleanEmail;
            }
        }

        // Fetch existing email recipient rows for this setting
        $existingEmailRows = $setting->recipients()->whereNotNull('email')->get();

        // Delete email rows that are no longer in the submitted list
        foreach ($existingEmailRows as $row) {
            if (!in_array(strtolower(trim($row->email)), $normalizedEmails, true)) {
                $row->delete();
            }
        }

        // Existing emails that are already stored
        $storedEmails = $existingEmailRows->pluck('email')->map(fn($e) => strtolower(trim($e)))->toArray();

        // Insert new email recipient rows that don't already exist
        foreach ($normalizedEmails as $email) {
            if (!in_array($email, $storedEmails, true)) {
                $setting->recipients()->create([
                    'user_id' => null,
                    'email' => $email,
                ]);
            }
        }
    }
}
