<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectNotificationSettingRequest;
use App\Models\ProjectNotificationSetting;
use App\Models\User;
use Illuminate\Http\Request;

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
        $setting = ProjectNotificationSetting::where('notification_type', 'timeline_ending_soon')->first();

        if (!$setting) {
            $setting = new ProjectNotificationSetting([
                'notification_type' => 'timeline_ending_soon',
                'is_enabled' => true,
                'days_before' => 3,
            ]);
            $selectedUsers = [];
        } else {
            $selectedUsers = $setting->users->pluck('id')->toArray();
        }

        $users = User::active()->get();

        return view('settings.project-notifications.index', [
            'setting' => $setting,
            'users' => $users,
            'selectedUsers' => $selectedUsers,
            'currentTab' => 'notifications',
            'createPermission' => 'project_settings.create',
            'editPermission' => 'project_settings.edit',
        ]);
    }

    public function store(ProjectNotificationSettingRequest $request)
    {
        $data = $request->validated();

        $setting = ProjectNotificationSetting::create([
            'notification_type' => 'timeline_ending_soon',
            'is_enabled' => $data['is_enabled'],
            'days_before' => $data['days_before'],
            'created_by' => auth()->id(),
        ]);

        $setting->users()->sync($data['user_ids'] ?? []);

        return redirect()->route('settings.project-notifications.index')
            ->with('success', 'Notification settings created successfully.');
    }

    public function update(ProjectNotificationSettingRequest $request, ProjectNotificationSetting $project_notification)
    {
        $data = $request->validated();

        $project_notification->update([
            'is_enabled' => $data['is_enabled'],
            'days_before' => $data['days_before'],
        ]);

        $project_notification->users()->sync($data['user_ids'] ?? []);

        return redirect()->route('settings.project-notifications.index')
            ->with('success', 'Notification settings updated successfully.');
    }
}
