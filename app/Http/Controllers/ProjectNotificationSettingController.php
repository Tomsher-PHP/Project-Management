<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectNotificationSettingRequest;
use App\Models\ProjectNotificationSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectNotificationSettingController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Project Notifications';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function index()
    {
        $setting = ProjectNotificationSetting::where('notification_type', ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON)->first();

        if (!$setting) {
            $setting = new ProjectNotificationSetting([
                'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON,
                'is_enabled' => true,
                'days_before' => 3,
            ]);
            $selectedUsers = [];
            $selectedEmails = [];
        } else {
            $selectedUsers = $setting->users->pluck('id')->toArray();
            $selectedEmails = $setting->recipients()
                ->whereNotNull('email')
                ->pluck('email')
                ->toArray();
        }

        $users = User::active()->get();

        return view('settings.project-notifications.index', [
            'setting' => $setting,
            'users' => $users,
            'selectedUsers' => $selectedUsers,
            'selectedEmails' => $selectedEmails,
            'currentTab' => 'notifications',
            'createPermission' => 'project_settings.create',
            'editPermission' => 'project_settings.edit',
        ]);
    }

    public function store(ProjectNotificationSettingRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $setting = ProjectNotificationSetting::create([
                'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON,
                'is_enabled' => $data['is_enabled'],
                'days_before' => $data['days_before'],
                'created_by' => auth()->id(),
            ]);

            $this->syncRecipients($setting, $data['user_ids'] ?? [], $data['emails'] ?? []);
        });

        return redirect()->route('settings.project-notifications.index')
            ->with('success', 'Notification settings created successfully.');
    }

    public function update(ProjectNotificationSettingRequest $request, ProjectNotificationSetting $project_notification)
    {
        // Ensure that the setting model being updated belongs to the timeline notification type
        if ($project_notification->notification_type !== ProjectNotificationSetting::NOTIFICATION_TYPE_TIMELINE_ENDING_SOON) {
            abort(404);
        }

        $data = $request->validated();

        DB::transaction(function () use ($project_notification, $data) {
            $project_notification->update([
                'is_enabled' => $data['is_enabled'],
                'days_before' => $data['days_before'],
            ]);

            $this->syncRecipients($project_notification, $data['user_ids'] ?? [], $data['emails'] ?? []);
        });

        return redirect()->route('settings.project-notifications.index')
            ->with('success', 'Notification settings updated successfully.');
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
