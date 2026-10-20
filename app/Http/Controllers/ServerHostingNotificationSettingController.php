<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectNotificationSettingRequest;
use App\Models\ProjectNotificationSetting;
use App\Models\User;
use Illuminate\Http\Request;

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
        $setting = ProjectNotificationSetting::where('notification_type', ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON)->first();

        if (!$setting) {
            $setting = new ProjectNotificationSetting([
                'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON,
                'is_enabled' => true,
                'days_before' => 30,
            ]);
            $selectedUsers = [];
        } else {
            $selectedUsers = $setting->users->pluck('id')->toArray();
        }

        $users = User::active()->get();

        return view('settings.server-hosting-notifications.index', [
            'setting' => $setting,
            'users' => $users,
            'selectedUsers' => $selectedUsers,
            'currentTab' => 'notifications',
            'createPermission' => 'server_hosting.create',
            'editPermission' => 'server_hosting.edit',
        ]);
    }

    public function store(ProjectNotificationSettingRequest $request)
    {
        $data = $request->validated();

        $setting = ProjectNotificationSetting::create([
            'notification_type' => ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON,
            'is_enabled' => $data['is_enabled'],
            'days_before' => $data['days_before'],
            'created_by' => auth()->id(),
        ]);

        $setting->users()->sync($data['user_ids'] ?? []);

        return redirect()->route('settings.server-hosting-notifications.index')
            ->with('success', 'Notification settings created successfully.');
    }

    public function update(ProjectNotificationSettingRequest $request, ProjectNotificationSetting $server_hosting_notification)
    {
        // Ensure that the setting model being updated belongs to the hosting notification type
        if ($server_hosting_notification->notification_type !== ProjectNotificationSetting::NOTIFICATION_TYPE_HOSTING_ENDING_SOON) {
            abort(404);
        }

        $data = $request->validated();

        $server_hosting_notification->update([
            'is_enabled' => $data['is_enabled'],
            'days_before' => $data['days_before'],
        ]);

        $server_hosting_notification->users()->sync($data['user_ids'] ?? []);

        return redirect()->route('settings.server-hosting-notifications.index')
            ->with('success', 'Notification settings updated successfully.');
    }
}
