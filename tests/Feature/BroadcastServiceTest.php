<?php

namespace Tests\Feature;

use App\Models\Broadcast;
use App\Models\User;
use App\Services\BroadcastService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BroadcastServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['attachments', 'broadcast_users', 'broadcasts', 'team_user', 'teams', 'broadcast_categories', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('delete_status')->default(false);
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

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Manager', 'email' => 'manager@example.com', 'is_active' => true, 'delete_status' => false],
            ['id' => 2, 'name' => 'User Two', 'email' => 'two@example.com', 'is_active' => true, 'delete_status' => false],
            ['id' => 3, 'name' => 'Inactive User', 'email' => 'three@example.com', 'is_active' => false, 'delete_status' => false],
        ]);
        DB::table('broadcast_categories')->insert([
            'id' => 1,
            'name' => 'General',
            'sort_order' => 1,
            'is_default' => true,
            'is_system' => false,
            'is_active' => true,
        ]);
        DB::table('teams')->insert([
            ['id' => 1, 'name' => 'Delivery', 'is_active' => true],
            ['id' => 2, 'name' => 'Support', 'is_active' => true],
        ]);
    }

    public function test_team_audience_deduplicates_eligible_members_and_switching_to_all_clears_allocations(): void
    {
        DB::table('team_user')->insert([
            ['team_id' => 1, 'user_id' => 1, 'is_active' => true],
            ['team_id' => 1, 'user_id' => 2, 'is_active' => true],
            ['team_id' => 2, 'user_id' => 1, 'is_active' => true],
            ['team_id' => 2, 'user_id' => 3, 'is_active' => true],
        ]);

        $service = app(BroadcastService::class);
        $actor = User::query()->findOrFail(1);
        $broadcast = $service->create($this->data([
            'audience_type' => Broadcast::AUDIENCE_TEAMS,
            'team_ids' => [1, 2],
        ]), $actor);

        $this->assertSame([1, 2], $broadcast->recipients()->orderBy('users.id')->pluck('users.id')->all());
        $this->assertSame([1, 2], $broadcast->target_team_ids);

        $broadcast = $service->update($broadcast, $this->data([
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'team_ids' => [],
        ]), $actor);

        $this->assertNull($broadcast->target_team_ids);
        $this->assertSame(0, $broadcast->recipients()->count());
    }

    public function test_publishing_refreshes_team_members_and_sets_publication_timestamp(): void
    {
        DB::table('team_user')->insert(['team_id' => 1, 'user_id' => 1, 'is_active' => true]);

        $service = app(BroadcastService::class);
        $actor = User::query()->findOrFail(1);
        $broadcast = $service->create($this->data([
            'audience_type' => Broadcast::AUDIENCE_TEAMS,
            'team_ids' => [1],
            'content' => '',
        ]), $actor);

        DB::table('team_user')->insert(['team_id' => 1, 'user_id' => 2, 'is_active' => true]);
        $broadcast = $service->changeStatus($broadcast, Broadcast::STATUS_PUBLISHED, $actor);

        $this->assertSame(Broadcast::STATUS_PUBLISHED, $broadcast->status);
        $this->assertNotNull($broadcast->published_at);
        $this->assertSame([1, 2], $broadcast->recipients()->orderBy('users.id')->pluck('users.id')->all());
    }

    protected function data(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Service broadcast',
            'content' => '<p>Safe content</p>',
            'broadcast_category_id' => 1,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'team_ids' => [],
            'user_ids' => [],
            'status' => Broadcast::STATUS_DRAFT,
            'start_date' => null,
            'end_date' => null,
        ], $overrides);
    }
}

