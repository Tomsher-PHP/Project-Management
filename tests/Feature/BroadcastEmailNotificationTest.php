<?php

namespace Tests\Feature;

use App\Jobs\DispatchBroadcastEmailsJob;
use App\Jobs\SendBroadcastEmailJob;
use App\Mail\BroadcastPublishedMail;
use App\Models\Attachment;
use App\Models\Broadcast;
use App\Models\BroadcastEmailDelivery;
use App\Models\User;
use App\Services\BroadcastEmailService;
use App\Services\BroadcastService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BroadcastEmailNotificationTest extends TestCase
{
    protected BroadcastService $broadcastService;
    protected BroadcastEmailService $broadcastEmailService;
    protected User $actor;
    protected int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();

        foreach (['broadcast_email_deliveries', 'attachments', 'broadcast_users', 'broadcasts', 'team_user', 'teams', 'broadcast_categories', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('delete_status')->default(false);
            $table->unsignedBigInteger('added_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('broadcast_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->unsignedBigInteger('broadcast_category_id');
            $table->unsignedTinyInteger('priority')->default(3);
            $table->string('audience_type', 20)->default('all');
            $table->json('target_team_ids')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('added_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('broadcast_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('broadcast_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['broadcast_id', 'user_id']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('link_id');
            $table->string('link_type');
            $table->string('category')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->string('disk')->default('local');
            $table->string('visibility')->default('private');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('broadcast_email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('broadcast_id');
            $table->unsignedBigInteger('user_id');
            $table->string('email');
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['broadcast_id', 'user_id']);
        });

        Schema::enableForeignKeyConstraints();

        $this->actor = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);

        $this->categoryId = DB::table('broadcast_categories')->insertGetId([
            'name' => 'General',
            'color' => '#10B981',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->broadcastService = app(BroadcastService::class);
        $this->broadcastEmailService = app(BroadcastEmailService::class);
    }

    public function test_create_as_draft_does_not_queue_emails(): void
    {
        Queue::fake();

        $this->broadcastService->create([
            'title' => 'Draft Announcement',
            'content' => '<p>Draft body</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_DRAFT,
        ], $this->actor);

        Queue::assertNothingPushed();
    }

    public function test_create_as_published_queues_email_dispatch_job(): void
    {
        Queue::fake();

        $broadcast = $this->broadcastService->create([
            'title' => 'Immediate Publication',
            'content' => '<p>Content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_URGENT,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        Queue::assertPushed(DispatchBroadcastEmailsJob::class, function ($job) use ($broadcast) {
            return $job->broadcastId === $broadcast->id;
        });
    }

    public function test_draft_to_published_queues_email_dispatch_job(): void
    {
        Queue::fake();

        $broadcast = $this->broadcastService->create([
            'title' => 'Draft to Publish',
            'content' => '<p>Draft content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_DRAFT,
        ], $this->actor);

        Queue::assertNothingPushed();

        $this->broadcastService->changeStatus($broadcast, Broadcast::STATUS_PUBLISHED, $this->actor);

        Queue::assertPushed(DispatchBroadcastEmailsJob::class, function ($job) use ($broadcast) {
            return $job->broadcastId === $broadcast->id;
        });
    }

    public function test_future_start_date_does_not_prevent_immediate_queue(): void
    {
        Queue::fake();

        $broadcast = $this->broadcastService->create([
            'title' => 'Future Broadcast',
            'content' => '<p>Future event content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_PUBLISHED,
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
        ], $this->actor);

        Queue::assertPushed(DispatchBroadcastEmailsJob::class, function ($job) use ($broadcast) {
            return $job->broadcastId === $broadcast->id;
        });
    }

    public function test_published_edit_does_not_requeue_emails(): void
    {
        Queue::fake();

        $broadcast = $this->broadcastService->create([
            'title' => 'Initial Publish',
            'content' => '<p>Initial content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        Queue::assertPushed(DispatchBroadcastEmailsJob::class, 1);

        // Clear queue tracking
        Queue::fake();

        $this->broadcastService->update($broadcast, [
            'title' => 'Updated Title',
            'content' => '<p>Updated content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
        ], $this->actor);

        Queue::assertNothingPushed();
    }

    public function test_archived_to_published_does_not_automatically_requeue_emails(): void
    {
        $broadcast = $this->broadcastService->create([
            'title' => 'Archived broadcast',
            'content' => '<p>Content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        $this->broadcastService->changeStatus($broadcast, Broadcast::STATUS_ARCHIVED, $this->actor);

        Queue::fake();

        // Re-publish from Archived
        $this->broadcastService->changeStatus($broadcast, Broadcast::STATUS_PUBLISHED, $this->actor);

        Queue::assertNothingPushed();
    }

    public function test_published_to_draft_is_rejected(): void
    {
        $broadcast = $this->broadcastService->create([
            'title' => 'Cannot revert',
            'content' => '<p>Content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        $this->expectException(\InvalidArgumentException::class);
        $this->broadcastService->changeStatus($broadcast, Broadcast::STATUS_DRAFT, $this->actor);
    }

    public function test_recipient_resolution_for_all_users_excludes_inactive_and_invalid(): void
    {
        $activeUser = User::create(['name' => 'Active 1', 'email' => 'active1@example.com', 'is_active' => true]);
        $inactiveUser = User::create(['name' => 'Inactive', 'email' => 'inactive@example.com', 'is_active' => false]);
        $deletedUser = User::create(['name' => 'Deleted', 'email' => 'deleted@example.com', 'is_active' => true, 'delete_status' => true]);
        $noEmailUser = User::create(['name' => 'No Email', 'email' => '', 'is_active' => true]);
        $invalidEmailUser = User::create(['name' => 'Bad Email', 'email' => 'not-an-email', 'is_active' => true]);

        $broadcast = Broadcast::create([
            'title' => 'All Audience',
            'content' => '<p>Hello</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_PUBLISHED,
            'added_by' => $this->actor->id,
        ]);

        $recipients = $this->broadcastEmailService->resolveEligibleRecipients($broadcast);
        $ids = $recipients->pluck('id')->all();

        $this->assertContains($this->actor->id, $ids);
        $this->assertContains($activeUser->id, $ids);
        $this->assertNotContains($inactiveUser->id, $ids);
        $this->assertNotContains($deletedUser->id, $ids);
        $this->assertNotContains($noEmailUser->id, $ids);
        $this->assertNotContains($invalidEmailUser->id, $ids);
    }

    public function test_dispatch_broadcast_creates_unique_deliveries_and_queues_send_jobs(): void
    {
        Queue::fake();

        $user1 = User::create(['name' => 'User One', 'email' => 'user1@example.com', 'is_active' => true]);
        $user2 = User::create(['name' => 'User Two', 'email' => 'user2@example.com', 'is_active' => true]);

        $broadcast = Broadcast::create([
            'title' => 'Team Updates',
            'content' => '<p>Meeting at 10am</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_USERS,
            'status' => Broadcast::STATUS_PUBLISHED,
            'added_by' => $this->actor->id,
        ]);

        DB::table('broadcast_users')->insert([
            ['broadcast_id' => $broadcast->id, 'user_id' => $user1->id, 'created_at' => now(), 'updated_at' => now()],
            ['broadcast_id' => $broadcast->id, 'user_id' => $user2->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->broadcastEmailService->dispatchBroadcast($broadcast->id);

        $deliveries = BroadcastEmailDelivery::where('broadcast_id', $broadcast->id)->get();
        $this->assertCount(2, $deliveries);

        $this->assertEquals(['user1@example.com', 'user2@example.com'], $deliveries->pluck('email')->sort()->values()->all());

        Queue::assertPushed(SendBroadcastEmailJob::class, 2);

        // Calling dispatch again does not recreate or duplicate
        $this->broadcastEmailService->dispatchBroadcast($broadcast->id);
        $this->assertCount(2, BroadcastEmailDelivery::where('broadcast_id', $broadcast->id)->get());
    }

    public function test_send_delivery_sends_mail_and_updates_status(): void
    {
        Mail::fake();

        $user = User::create(['name' => 'Target User', 'email' => 'target@example.com', 'is_active' => true]);

        $broadcast = Broadcast::create([
            'title' => 'Critical Policy',
            'content' => '<p>Important update</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_URGENT,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_PUBLISHED,
            'added_by' => $this->actor->id,
        ]);

        $delivery = BroadcastEmailDelivery::create([
            'broadcast_id' => $broadcast->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => BroadcastEmailDelivery::STATUS_PENDING,
            'attempts' => 0,
        ]);

        $this->broadcastEmailService->sendDelivery($delivery->id);

        $delivery->refresh();
        $this->assertEquals(BroadcastEmailDelivery::STATUS_SENT, $delivery->status);
        $this->assertNotNull($delivery->sent_at);
        $this->assertEquals(1, $delivery->attempts);

        Mail::assertSent(BroadcastPublishedMail::class, function ($mail) use ($user, $broadcast) {
            return $mail->hasTo('target@example.com')
                && $mail->envelope()->subject === "[Urgent] {$broadcast->title}";
        });
    }

    public function test_archived_broadcast_is_skipped_before_send(): void
    {
        Mail::fake();

        $user = User::create(['name' => 'Target User', 'email' => 'target2@example.com', 'is_active' => true]);

        $broadcast = Broadcast::create([
            'title' => 'Will Archive Soon',
            'content' => '<p>Content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_ARCHIVED, // Archived prior to sending
            'added_by' => $this->actor->id,
        ]);

        $delivery = BroadcastEmailDelivery::create([
            'broadcast_id' => $broadcast->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => BroadcastEmailDelivery::STATUS_PENDING,
            'attempts' => 0,
        ]);

        $this->broadcastEmailService->sendDelivery($delivery->id);

        $delivery->refresh();
        $this->assertEquals(BroadcastEmailDelivery::STATUS_FAILED, $delivery->status);
        $this->assertStringContainsString('archived', $delivery->error_message);

        Mail::assertNothingSent();
    }
}
