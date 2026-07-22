<?php

namespace Tests\Feature;

use App\Models\Appraisal;
use App\Models\Kpi;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Notifications\TaskAssignedNotification;
use App\Services\AppraisalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AppraisalAssignmentNotificationTest extends TestCase
{
    public function test_single_assignment_sends_notification_only_to_assignee(): void
    {
        Notification::fake();

        $actor = User::factory()->create(['is_super_admin' => true]);
        $assignee = User::factory()->create();
        $otherUser = User::factory()->create();

        UserNotificationSetting::create([
            'user_id' => $assignee->id,
            'action' => UserNotificationSetting::APPRAISAL_ASSIGNED,
            'mail' => true,
            'in_app' => true,
        ]);

        UserNotificationSetting::create([
            'user_id' => $otherUser->id,
            'action' => UserNotificationSetting::APPRAISAL_ASSIGNED,
            'mail' => true,
            'in_app' => true,
        ]);

        $kpi = Kpi::create([
            'name' => 'Test KPI',
            'description' => 'Test KPI description',
            'is_active' => true,
            'is_system' => false,
        ]);

        $this->actingAs($actor);

        $service = app(AppraisalService::class);
        $service->assign([
            'month' => 7,
            'year' => 2026,
            'status' => 'draft',
            'kpi_id' => $kpi->id,
            'user_ids' => [$assignee->id],
            'categories' => [[
                'name' => 'Performance',
                'questions' => [[
                    'question' => 'How did the person perform?',
                    'question_type' => 'rating',
                ]],
            ]],
        ]);

        Notification::assertSentToTimes($assignee, TaskAssignedNotification::class, 1);
        Notification::assertSentTo($assignee, TaskAssignedNotification::class, function ($notification, $channels) use ($assignee) {
            $payload = $notification->toArray($assignee);

            return $payload['title'] === 'New Appraisal Assigned'
                && str_contains($payload['message'], 'July 2026')
                && $notification instanceof ShouldQueue
                && in_array('database', $channels, true);
        });
        Notification::assertNotSentTo($otherUser, TaskAssignedNotification::class);
    }

    public function test_draft_assignment_does_not_send_notification(): void
    {
        Notification::fake();

        $actor = User::factory()->create(['is_super_admin' => true]);
        $assignee = User::factory()->create();

        UserNotificationSetting::create([
            'user_id' => $assignee->id,
            'action' => UserNotificationSetting::APPRAISAL_ASSIGNED,
            'mail' => true,
            'in_app' => true,
        ]);

        $kpi = Kpi::create([
            'name' => 'Draft KPI',
            'description' => 'Draft KPI description',
            'is_active' => true,
            'is_system' => false,
        ]);

        $this->actingAs($actor);

        $service = app(AppraisalService::class);
        $service->assign([
            'month' => 9,
            'year' => 2026,
            'status' => 'draft',
            'kpi_id' => $kpi->id,
            'user_ids' => [$assignee->id],
            'categories' => [[
                'name' => 'Performance',
                'questions' => [[
                    'question' => 'How did the person perform?',
                    'question_type' => 'rating',
                ]],
            ]],
        ]);

        Notification::assertNotSentTo($assignee, TaskAssignedNotification::class);
    }

    public function test_bulk_assignment_sends_one_notification_per_assignee(): void
    {
        Notification::fake();

        $actor = User::factory()->create(['is_super_admin' => true]);
        $firstAssignee = User::factory()->create();
        $secondAssignee = User::factory()->create();

        foreach ([$firstAssignee, $secondAssignee] as $user) {
            UserNotificationSetting::create([
                'user_id' => $user->id,
                'action' => UserNotificationSetting::APPRAISAL_ASSIGNED,
                'mail' => true,
                'in_app' => true,
            ]);
        }

        $kpi = Kpi::create([
            'name' => 'Bulk KPI',
            'description' => 'Bulk KPI description',
            'is_active' => true,
            'is_system' => false,
        ]);

        $this->actingAs($actor);

        $service = app(AppraisalService::class);
        $service->assign([
            'month' => 8,
            'year' => 2026,
            'status' => 'published',
            'kpi_id' => $kpi->id,
            'user_ids' => [$firstAssignee->id, $secondAssignee->id],
            'categories' => [[
                'name' => 'Delivery',
                'questions' => [[
                    'question' => 'How was the delivery?',
                    'question_type' => 'answer',
                ]],
            ]],
        ]);

        Notification::assertSentToTimes($firstAssignee, TaskAssignedNotification::class, 1);
        Notification::assertSentToTimes($secondAssignee, TaskAssignedNotification::class, 1);
        Notification::assertSentTo($firstAssignee, TaskAssignedNotification::class, function ($notification, $channels) use ($firstAssignee) {
            return $notification->toArray($firstAssignee)['title'] === 'New Appraisal Assigned'
                && in_array('database', $channels, true);
        });
    }
}
