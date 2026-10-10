<?php

use App\Models\ProjectNotificationSetting;
use App\Models\ProjectTimeline;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Carbon;

// Setup basic requirements
$user = User::first();
if (!$user) {
    echo "No user found.\n";
    exit;
}

$project = Project::firstOrCreate(['name' => 'Test Project Job 3']);

// Create setting
$setting = ProjectNotificationSetting::firstOrCreate(
    ['notification_type' => 'timeline_ending_soon'],
    [
        'is_enabled' => true,
        'days_before' => 3,
        'created_by' => $user->id,
    ]
);
$setting->update(['is_enabled' => true, 'days_before' => 3]);
$setting->users()->sync([$user->id]);

// Test missing end dates
ProjectTimeline::create([
    'project_id' => $project->id,
    'name' => 'No End Date Timeline',
    'status' => ProjectTimeline::STATUS_ACTIVE,
    'type' => 'new',
    'start_date' => Carbon::today(),
    'created_by' => $user->id,
]);

// Test non-active timelines
ProjectTimeline::create([
    'project_id' => $project->id,
    'name' => 'Non Active Timeline',
    'status' => ProjectTimeline::STATUS_COMPLETED,
    'type' => 'new',
    'start_date' => Carbon::today(),
    'end_date' => Carbon::today()->addDays(3),
    'created_by' => $user->id,
]);

// Test future notification (not due today)
ProjectTimeline::create([
    'project_id' => $project->id,
    'name' => 'Future Timeline',
    'status' => ProjectTimeline::STATUS_ACTIVE,
    'type' => 'new',
    'start_date' => Carbon::today(),
    'end_date' => Carbon::today()->addDays(4),
    'created_by' => $user->id,
]);

// Test due notification
$dueTimeline = ProjectTimeline::create([
    'project_id' => $project->id,
    'name' => 'Due Timeline',
    'status' => ProjectTimeline::STATUS_ACTIVE,
    'type' => 'new',
    'start_date' => Carbon::today(),
    'end_date' => Carbon::today()->addDays(3),
    'created_by' => $user->id,
]);

echo "Test data setup complete. Please run: php artisan project:timeline-ending-soon-notifications\n";
