<?php

namespace Tests\Feature;

use App\Jobs\DispatchBroadcastEmailsJob;
use App\Jobs\SendBroadcastEmailJob;
use App\Mail\BroadcastPublishedMail;
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

        foreach ([
            'broadcast_email_deliveries',
            'attachments',
            'broadcast_users',
            'broadcasts',
            'team_user',
            'teams',
            'broadcast_categories',
            'configurations',
            'users',
        ] as $table) {
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

        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('company_email')->nullable();
            $table->string('date_format')->nullable();
            $table->string('time_format')->nullable();
            $table->string('timezone')->nullable();
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

        config([
            'mail.from.address' => 'noreply@company.com',
            'mail.from.name' => 'Company Name',
        ]);

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

        Queue::assertPushed(DispatchBroadcastEmailsJob::class, 1);
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

        Queue::assertPushed(DispatchBroadcastEmailsJob::class, 1);
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

    public function test_published_to_archived_does_not_queue_emails(): void
    {
        Mail::fake();

        $broadcast = $this->broadcastService->create([
            'title' => 'Archive Me',
            'content' => '<p>Content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        Mail::fake();
        Queue::fake();

        $this->broadcastService->changeStatus($broadcast, Broadcast::STATUS_ARCHIVED, $this->actor);

        Queue::assertNothingPushed();
        Mail::assertNothingSent();
    }

    public function test_archived_to_published_does_not_automatically_requeue_emails(): void
    {
        Mail::fake();

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
        Mail::fake();

        $this->broadcastService->changeStatus($broadcast, Broadcast::STATUS_PUBLISHED, $this->actor);

        Queue::assertNothingPushed();
        Mail::assertNothingSent();
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

    public function test_publish_to_all_users_sends_one_bcc_email_and_excludes_ineligible_and_duplicates(): void
    {
        Mail::fake();

        User::create(['name' => 'Active 1', 'email' => 'employee1@example.com', 'is_active' => true]);
        User::create(['name' => 'Active 2', 'email' => 'employee2@example.com', 'is_active' => true]);
        User::create(['name' => 'Duplicate Case', 'email' => 'EMPLOYEE1@example.com', 'is_active' => true]);
        User::create(['name' => 'Sender Dup', 'email' => 'noreply@company.com', 'is_active' => true]);
        User::create(['name' => 'Inactive', 'email' => 'inactive@example.com', 'is_active' => false]);
        User::create(['name' => 'Flag Deleted', 'email' => 'deleted@example.com', 'is_active' => true, 'delete_status' => true]);
        $softDeleted = User::create(['name' => 'Soft Deleted', 'email' => 'softdeleted@example.com', 'is_active' => true]);
        $softDeleted->delete();
        User::create(['name' => 'No Email', 'email' => '', 'is_active' => true]);
        User::create(['name' => 'Null Email', 'email' => null, 'is_active' => true]);
        User::create(['name' => 'Bad Email', 'email' => 'not-an-email', 'is_active' => true]);

        $broadcast = $this->broadcastService->create([
            'title' => 'All Company Update',
            'content' => '<p>Hello everyone</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_URGENT,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        Mail::assertSent(BroadcastPublishedMail::class, 1);
        Mail::assertSent(BroadcastPublishedMail::class, function (BroadcastPublishedMail $mail) use ($broadcast) {
            $bccAddresses = collect($mail->bcc)->pluck('address')->sort()->values()->all();
            $toAddresses = collect($mail->to)->pluck('address')->all();
            $ccAddresses = collect($mail->cc)->pluck('address')->all();

            return $mail->hasFrom('noreply@company.com')
                && $toAddresses === ['noreply@company.com']
                && $ccAddresses === []
                && $bccAddresses === ['admin@example.com', 'employee1@example.com', 'employee2@example.com']
                && $mail->envelope()->subject === "[Urgent] {$broadcast->title}";
        });

        $deliveries = BroadcastEmailDelivery::where('broadcast_id', $broadcast->id)->get();
        $this->assertTrue($deliveries->every(fn ($d) => $d->status === BroadcastEmailDelivery::STATUS_SENT && $d->attempts === 1));
    }

    public function test_publish_to_specific_team_sends_one_bcc_email_only_to_selected_team(): void
    {
        Mail::fake();

        $dev1 = User::create(['name' => 'Dev 1', 'email' => 'dev1@company.com', 'is_active' => true]);
        $dev2 = User::create(['name' => 'Dev 2', 'email' => 'dev2@company.com', 'is_active' => true]);
        $other = User::create(['name' => 'Other Team', 'email' => 'other@company.com', 'is_active' => true]);

        $engTeamId = DB::table('teams')->insertGetId(['name' => 'Engineering', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $hrTeamId = DB::table('teams')->insertGetId(['name' => 'HR', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        DB::table('team_user')->insert([
            ['team_id' => $engTeamId, 'user_id' => $dev1->id, 'is_active' => true],
            ['team_id' => $engTeamId, 'user_id' => $dev2->id, 'is_active' => true],
            ['team_id' => $hrTeamId, 'user_id' => $other->id, 'is_active' => true],
        ]);

        $this->broadcastService->create([
            'title' => 'Engineering Notice',
            'content' => '<p>Deploy schedule</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_TEAMS,
            'team_ids' => [$engTeamId],
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        Mail::assertSent(BroadcastPublishedMail::class, 1);
        Mail::assertSent(BroadcastPublishedMail::class, function (BroadcastPublishedMail $mail) {
            $bcc = collect($mail->bcc)->pluck('address')->sort()->values()->all();

            return $mail->hasTo('noreply@company.com')
                && $bcc === ['dev1@company.com', 'dev2@company.com'];
        });
    }

    public function test_publish_to_multiple_teams_deduplicates_shared_members(): void
    {
        Mail::fake();

        $shared = User::create(['name' => 'Shared User', 'email' => 'shared@company.com', 'is_active' => true]);
        $dev = User::create(['name' => 'Dev User', 'email' => 'dev@company.com', 'is_active' => true]);
        $des = User::create(['name' => 'Designer', 'email' => 'designer@company.com', 'is_active' => true]);
        $unselected = User::create(['name' => 'Unselected', 'email' => 'unselected@company.com', 'is_active' => true]);

        $teamA = DB::table('teams')->insertGetId(['name' => 'Team A', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $teamB = DB::table('teams')->insertGetId(['name' => 'Team B', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $teamC = DB::table('teams')->insertGetId(['name' => 'Team C', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        DB::table('team_user')->insert([
            ['team_id' => $teamA, 'user_id' => $shared->id, 'is_active' => true],
            ['team_id' => $teamA, 'user_id' => $dev->id, 'is_active' => true],
            ['team_id' => $teamB, 'user_id' => $shared->id, 'is_active' => true],
            ['team_id' => $teamB, 'user_id' => $des->id, 'is_active' => true],
            ['team_id' => $teamC, 'user_id' => $unselected->id, 'is_active' => true],
        ]);

        $this->broadcastService->create([
            'title' => 'Multi Team Sync',
            'content' => '<p>Cross-team sync</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_IMPORTANT,
            'audience_type' => Broadcast::AUDIENCE_TEAMS,
            'team_ids' => [$teamA, $teamB],
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        Mail::assertSent(BroadcastPublishedMail::class, 1);
        Mail::assertSent(BroadcastPublishedMail::class, function (BroadcastPublishedMail $mail) {
            $bcc = collect($mail->bcc)->pluck('address')->sort()->values()->all();

            return $mail->hasTo('noreply@company.com')
                && $bcc === ['designer@company.com', 'dev@company.com', 'shared@company.com'];
        });
    }

    public function test_publish_to_specific_users_sends_one_bcc_email_with_only_selected_eligible_users(): void
    {
        Mail::fake();

        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@company.com', 'is_active' => true]);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@company.com', 'is_active' => true]);
        $user3 = User::create(['name' => 'User 3', 'email' => 'user3@company.com', 'is_active' => true]);
        $notSelected = User::create(['name' => 'Other', 'email' => 'other@company.com', 'is_active' => true]);

        $this->broadcastService->create([
            'title' => 'Selected Users Notice',
            'content' => '<p>Private notice</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_USERS,
            'user_ids' => [$user1->id, $user2->id, $user3->id],
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        Mail::assertSent(BroadcastPublishedMail::class, 1);
        Mail::assertSent(BroadcastPublishedMail::class, function (BroadcastPublishedMail $mail) use ($notSelected) {
            $bcc = collect($mail->bcc)->pluck('address')->sort()->values()->all();

            return $mail->hasTo('noreply@company.com')
                && $bcc === ['user1@company.com', 'user2@company.com', 'user3@company.com']
                && ! $mail->hasTo('user1@company.com')
                && ! $mail->hasCc('user1@company.com')
                && ! in_array($notSelected->email, $bcc, true);
        });
    }

    public function test_only_one_eligible_recipient_sends_one_email_with_single_bcc(): void
    {
        Mail::fake();

        $onlyUser = User::create(['name' => 'Sole User', 'email' => 'sole@company.com', 'is_active' => true]);

        $this->broadcastService->create([
            'title' => 'Single Recipient',
            'content' => '<p>One recipient</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_USERS,
            'user_ids' => [$onlyUser->id],
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        Mail::assertSent(BroadcastPublishedMail::class, 1);
        Mail::assertSent(BroadcastPublishedMail::class, function (BroadcastPublishedMail $mail) {
            return $mail->hasTo('noreply@company.com')
                && collect($mail->bcc)->pluck('address')->all() === ['sole@company.com'];
        });
    }

    public function test_no_eligible_recipients_queues_no_send_job_and_sends_no_email(): void
    {
        Queue::fake();
        Mail::fake();

        // Deactivate actor and create broadcast with no valid recipients
        DB::table('users')->where('id', $this->actor->id)->update(['is_active' => false]);

        $broadcast = Broadcast::create([
            'title' => 'Empty Audience',
            'content' => '<p>Nobody here</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_PUBLISHED,
            'added_by' => $this->actor->id,
        ]);

        $this->broadcastEmailService->dispatchBroadcast($broadcast->id);

        $this->assertCount(0, BroadcastEmailDelivery::where('broadcast_id', $broadcast->id)->get());
        Queue::assertNothingPushed();
        Mail::assertNothingSent();
    }

    public function test_dispatch_broadcast_queues_exactly_one_send_job_and_prevents_duplicates(): void
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

        Queue::assertPushed(SendBroadcastEmailJob::class, 1);
        Queue::assertPushed(SendBroadcastEmailJob::class, function ($job) use ($broadcast) {
            return $job->broadcastId === $broadcast->id;
        });

        // Calling dispatch again (e.g. concurrent dispatch attempt) does not recreate deliveries or queue another job
        $this->broadcastEmailService->dispatchBroadcast($broadcast->id);

        $this->assertCount(2, BroadcastEmailDelivery::where('broadcast_id', $broadcast->id)->get());
        Queue::assertPushed(SendBroadcastEmailJob::class, 1);
    }

    public function test_concurrent_or_repeated_send_broadcast_calls_do_not_send_duplicate_emails(): void
    {
        Mail::fake();

        $user1 = User::create(['name' => 'User One', 'email' => 'user1@example.com', 'is_active' => true]);
        $user2 = User::create(['name' => 'User Two', 'email' => 'user2@example.com', 'is_active' => true]);

        $broadcast = Broadcast::create([
            'title' => 'Idempotent Send',
            'content' => '<p>Content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_USERS,
            'status' => Broadcast::STATUS_PUBLISHED,
            'added_by' => $this->actor->id,
        ]);

        foreach ([$user1, $user2] as $u) {
            BroadcastEmailDelivery::create([
                'broadcast_id' => $broadcast->id,
                'user_id' => $u->id,
                'email' => $u->email,
                'status' => BroadcastEmailDelivery::STATUS_PENDING,
                'attempts' => 0,
            ]);
        }

        $this->broadcastEmailService->sendBroadcast($broadcast->id);
        $this->broadcastEmailService->sendBroadcast($broadcast->id);

        Mail::assertSent(BroadcastPublishedMail::class, 1);
    }

    public function test_processing_broadcast_is_not_sent_again_by_concurrent_worker(): void
    {
        Mail::fake();

        $user = User::create(['name' => 'User One', 'email' => 'user1@example.com', 'is_active' => true]);

        $broadcast = Broadcast::create([
            'title' => 'In Progress Broadcast',
            'content' => '<p>Content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_USERS,
            'status' => Broadcast::STATUS_PUBLISHED,
            'added_by' => $this->actor->id,
        ]);

        BroadcastEmailDelivery::create([
            'broadcast_id' => $broadcast->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => BroadcastEmailDelivery::STATUS_PROCESSING,
            'attempts' => 1,
        ]);

        $this->broadcastEmailService->sendBroadcast($broadcast->id);

        Mail::assertNothingSent();
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
            'status' => Broadcast::STATUS_ARCHIVED,
            'added_by' => $this->actor->id,
        ]);

        $delivery = BroadcastEmailDelivery::create([
            'broadcast_id' => $broadcast->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => BroadcastEmailDelivery::STATUS_PENDING,
            'attempts' => 0,
        ]);

        $this->broadcastEmailService->sendBroadcast($broadcast->id);

        $delivery->refresh();
        $this->assertEquals(BroadcastEmailDelivery::STATUS_FAILED, $delivery->status);
        $this->assertStringContainsString('archived', $delivery->error_message);

        Mail::assertNothingSent();
    }

    public function test_smtp_failure_records_failure_and_allows_safe_retry(): void
    {
        $user1 = User::create(['name' => 'User 1', 'email' => 'retry1@company.com', 'is_active' => true]);
        $user2 = User::create(['name' => 'User 2', 'email' => 'retry2@company.com', 'is_active' => true]);

        $broadcast = Broadcast::create([
            'title' => 'Retryable Broadcast',
            'content' => '<p>Content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_USERS,
            'status' => Broadcast::STATUS_PUBLISHED,
            'added_by' => $this->actor->id,
        ]);

        foreach ([$user1, $user2] as $u) {
            BroadcastEmailDelivery::create([
                'broadcast_id' => $broadcast->id,
                'user_id' => $u->id,
                'email' => $u->email,
                'status' => BroadcastEmailDelivery::STATUS_PENDING,
                'attempts' => 0,
            ]);
        }

        $realMailManager = $this->app->make('mail.manager');

        Mail::shouldReceive('to')
            ->once()
            ->with('noreply@company.com')
            ->andThrow(new \RuntimeException('SMTP connection timed out'));

        try {
            $this->broadcastEmailService->sendBroadcast($broadcast->id);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('SMTP connection timed out', $e->getMessage());
        }

        $deliveries = BroadcastEmailDelivery::where('broadcast_id', $broadcast->id)->get();
        $this->assertTrue($deliveries->every(fn ($d) => $d->status === BroadcastEmailDelivery::STATUS_FAILED && $d->attempts === 1));
        $this->assertStringContainsString('SMTP connection timed out', $deliveries->first()->error_message);

        // Restore real MailManager and fake mail for retry
        $this->app->instance('mail.manager', $realMailManager);
        Mail::clearResolvedInstance('mail.manager');
        Mail::fake();

        $this->broadcastEmailService->sendBroadcast($broadcast->id);

        $deliveries = BroadcastEmailDelivery::where('broadcast_id', $broadcast->id)->get();
        $this->assertTrue($deliveries->every(fn ($d) => $d->status === BroadcastEmailDelivery::STATUS_SENT && $d->attempts === 2 && $d->error_message === null));

        Mail::assertSent(BroadcastPublishedMail::class, 1);
    }

    public function test_job_failed_hook_marks_processing_deliveries_as_failed(): void
    {
        $user = User::create(['name' => 'User 1', 'email' => 'u1@company.com', 'is_active' => true]);

        $broadcast = Broadcast::create([
            'title' => 'Exhausted Retries',
            'content' => '<p>Content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_USERS,
            'status' => Broadcast::STATUS_PUBLISHED,
            'added_by' => $this->actor->id,
        ]);

        $delivery = BroadcastEmailDelivery::create([
            'broadcast_id' => $broadcast->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => BroadcastEmailDelivery::STATUS_PROCESSING,
            'attempts' => 3,
        ]);

        $job = new SendBroadcastEmailJob($broadcast->id);
        $job->failed(new \RuntimeException('Max retries exceeded'));

        $delivery->refresh();
        $this->assertEquals(BroadcastEmailDelivery::STATUS_FAILED, $delivery->status);
        $this->assertSame('Max retries exceeded', $delivery->error_message);
    }

    public function test_recipient_limit_exceeded_fails_clearly_with_no_partial_sending(): void
    {
        Mail::fake();
        // Limit is 3 total (1 To + max 2 BCC). With 3 BCC recipients, total = 4 > 3.
        config(['mail.max_recipients' => 3]);

        $u1 = User::create(['name' => 'U1', 'email' => 'u1@company.com', 'is_active' => true]);
        $u2 = User::create(['name' => 'U2', 'email' => 'u2@company.com', 'is_active' => true]);
        $u3 = User::create(['name' => 'U3', 'email' => 'u3@company.com', 'is_active' => true]);

        $broadcast = $this->broadcastService->create([
            'title' => 'Too Many Recipients',
            'content' => '<p>Content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_USERS,
            'user_ids' => [$u1->id, $u2->id, $u3->id],
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        Mail::assertNothingSent();

        $deliveries = BroadcastEmailDelivery::where('broadcast_id', $broadcast->id)->get();
        $this->assertCount(3, $deliveries);
        $this->assertTrue($deliveries->every(fn ($d) => $d->status === BroadcastEmailDelivery::STATUS_FAILED));
        $this->assertStringContainsString('exceeds the configured SMTP recipient limit of 3', $deliveries->first()->error_message);
    }

    public function test_recipient_count_at_exact_limit_including_to_succeeds(): void
    {
        Mail::fake();
        // Limit is 3 total: 1 To + 2 BCC = 3 (allowed)
        config(['mail.max_recipients' => 3]);

        $u1 = User::create(['name' => 'U1', 'email' => 'u1@company.com', 'is_active' => true]);
        $u2 = User::create(['name' => 'U2', 'email' => 'u2@company.com', 'is_active' => true]);

        $broadcast = $this->broadcastService->create([
            'title' => 'Exact Limit',
            'content' => '<p>Content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_USERS,
            'user_ids' => [$u1->id, $u2->id],
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        Mail::assertSent(BroadcastPublishedMail::class, 1);
        $deliveries = BroadcastEmailDelivery::where('broadcast_id', $broadcast->id)->get();
        $this->assertTrue($deliveries->every(fn ($d) => $d->status === BroadcastEmailDelivery::STATUS_SENT));
    }

    public function test_mailable_renders_cleanly_without_individual_recipient(): void
    {
        $broadcast = Broadcast::create([
            'title' => 'Render Check',
            'content' => '<p>Body text</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_PUBLISHED,
            'added_by' => $this->actor->id,
        ]);

        $html = (new BroadcastPublishedMail($broadcast))->render();

        $this->assertStringContainsString('Render Check', $html);
        $this->assertStringContainsString('Hello,', $html);
        $this->assertStringNotContainsString('Hello ,', $html);
    }

    public function test_missing_mail_from_address_aborts_cleanly_and_marks_failed(): void
    {
        Mail::fake();
        config(['mail.from.address' => '']);

        $user = User::create(['name' => 'User 1', 'email' => 'u1@company.com', 'is_active' => true]);

        $broadcast = $this->broadcastService->create([
            'title' => 'Missing Sender',
            'content' => '<p>Content</p>',
            'broadcast_category_id' => $this->categoryId,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_USERS,
            'user_ids' => [$user->id],
            'status' => Broadcast::STATUS_PUBLISHED,
        ], $this->actor);

        Mail::assertNothingSent();

        $delivery = BroadcastEmailDelivery::where('broadcast_id', $broadcast->id)->first();
        $this->assertNotNull($delivery);
        $this->assertEquals(BroadcastEmailDelivery::STATUS_FAILED, $delivery->status);
        $this->assertStringContainsString('MAIL_FROM_ADDRESS', $delivery->error_message);
    }
}
