<?php

namespace Tests\Feature;

use App\Models\Broadcast;
use App\Models\User;
use App\Services\BroadcastService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BroadcastWorkAnniversaryTest extends TestCase
{
    protected function setUp(): void
    {
        $compiledViewsPath = sys_get_temp_dir() . '/pm_test_views';
        if (! is_dir($compiledViewsPath)) {
            mkdir($compiledViewsPath, 0777, true);
        }
        putenv("VIEW_COMPILED_PATH={$compiledViewsPath}");
        $_ENV['VIEW_COMPILED_PATH'] = $compiledViewsPath;
        $_SERVER['VIEW_COMPILED_PATH'] = $compiledViewsPath;

        parent::setUp();

        Schema::disableForeignKeyConstraints();

        foreach ([
            'attachments',
            'broadcast_users',
            'broadcasts',
            'broadcast_categories',
            'user_details',
            'designations',
            'departments',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('delete_status')->default(false);
            $table->unsignedBigInteger('added_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->date('joining_date')->nullable();
            $table->date('dob')->nullable();
            $table->timestamps();
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
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('disk')->default('local');
            $table->string('visibility')->default('private');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
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

        Schema::enableForeignKeyConstraints();

        DB::table('departments')->insert(['id' => 1, 'name' => 'Engineering']);
        DB::table('designations')->insert(['id' => 1, 'name' => 'Senior Developer']);
        DB::table('broadcast_categories')->insert([
            'id' => 1,
            'name' => 'General',
            'color' => '#10B981',
            'sort_order' => 1,
            'is_default' => true,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_get_today_work_anniversaries_returns_only_todays_completed_years_for_active_users(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 11, 10, 0, 0));

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Alice Self', 'email' => 'alice@example.com', 'is_active' => true, 'delete_status' => false, 'deleted_at' => null],
            ['id' => 2, 'name' => 'Bob Three', 'email' => 'bob@example.com', 'is_active' => true, 'delete_status' => false, 'deleted_at' => null],
            ['id' => 3, 'name' => 'Joined Today', 'email' => 'new@example.com', 'is_active' => true, 'delete_status' => false, 'deleted_at' => null],
            ['id' => 4, 'name' => 'Upcoming Tomorrow', 'email' => 'upcoming@example.com', 'is_active' => true, 'delete_status' => false, 'deleted_at' => null],
            ['id' => 5, 'name' => 'Inactive User', 'email' => 'inactive@example.com', 'is_active' => false, 'delete_status' => false, 'deleted_at' => null],
            ['id' => 6, 'name' => 'Soft Deleted', 'email' => 'deleted@example.com', 'is_active' => true, 'delete_status' => false, 'deleted_at' => now()],
            ['id' => 7, 'name' => 'Flag Deleted', 'email' => 'flag@example.com', 'is_active' => true, 'delete_status' => true, 'deleted_at' => null],
        ]);

        DB::table('user_details')->insert([
            ['user_id' => 1, 'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2025-11-11'],
            ['user_id' => 2, 'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2023-11-11'],
            ['user_id' => 3, 'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2026-11-11'],
            ['user_id' => 4, 'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2024-11-12'],
            ['user_id' => 5, 'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2022-11-11'],
            ['user_id' => 6, 'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2022-11-11'],
            ['user_id' => 7, 'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2022-11-11'],
        ]);

        $service = app(BroadcastService::class);
        $anniversaries = $service->getTodayWorkAnniversaries(1);

        $this->assertCount(2, $anniversaries);
        // Sorted by years desc: Bob (3 years) then Alice (1 year)
        $this->assertSame(2, $anniversaries[0]->user->id);
        $this->assertSame(3, $anniversaries[0]->years);
        $this->assertFalse($anniversaries[0]->is_self);

        $this->assertSame(1, $anniversaries[1]->user->id);
        $this->assertSame(1, $anniversaries[1]->years);
        $this->assertTrue($anniversaries[1]->is_self);
    }

    public function test_leap_day_joining_date_is_celebrated_on_feb_28_in_non_leap_year(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 28, 9, 0, 0));

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Leap Joiner', 'email' => 'leap@example.com', 'is_active' => true, 'delete_status' => false],
        ]);
        DB::table('user_details')->insert([
            ['user_id' => 1, 'joining_date' => '2024-02-29'],
        ]);

        $anniversaries = app(BroadcastService::class)->getTodayWorkAnniversaries(1);

        $this->assertCount(1, $anniversaries);
        $this->assertSame(2, $anniversaries[0]->years);
        $this->assertSame('2026-02-28', $anniversaries[0]->anniversary_date->toDateString());
    }

    public function test_drawer_count_and_drawer_list_include_only_todays_work_anniversaries(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 11, 10, 0, 0));

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Alice Employee', 'email' => 'alice@example.com', 'is_active' => true, 'delete_status' => false],
            ['id' => 2, 'name' => 'Bob Veteran', 'email' => 'bob@example.com', 'is_active' => true, 'delete_status' => false],
            ['id' => 3, 'name' => 'Upcoming Only', 'email' => 'soon@example.com', 'is_active' => true, 'delete_status' => false],
        ]);

        DB::table('user_details')->insert([
            ['user_id' => 1, 'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2025-11-11'],
            ['user_id' => 2, 'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2021-11-11'],
            ['user_id' => 3, 'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2023-11-13'],
        ]);

        DB::table('broadcasts')->insert([
            'id' => 1,
            'title' => 'Company Townhall',
            'content' => '<p>Join us</p>',
            'broadcast_category_id' => 1,
            'priority' => Broadcast::PRIORITY_NORMAL,
            'audience_type' => Broadcast::AUDIENCE_ALL,
            'status' => Broadcast::STATUS_PUBLISHED,
            'added_by' => 1,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $authUser = User::query()->findOrFail(1);

        $countResponse = $this->actingAs($authUser)->getJson(route('broadcasts.drawer.count'));
        $countResponse->assertOk()
            ->assertJson([
                'status' => true,
                'count' => 3,
                'broadcast_count' => 1,
                'birthday_count' => 0,
                'work_anniversary_count' => 2,
                'today_work_anniversary_count' => 2,
                'has_today_birthday' => false,
                'has_today_work_anniversary' => true,
                'label' => '3 Broadcasts',
            ]);

        $listResponse = $this->actingAs($authUser)->getJson(route('broadcasts.drawer.list', ['page' => 1]));
        $listResponse->assertOk()
            ->assertJson([
                'status' => true,
                'total' => 3,
                'broadcast_total' => 1,
                'has_today_birthday' => false,
                'has_today_work_anniversary' => true,
            ]);

        $html = $listResponse->json('html');
        $this->assertStringContainsString('data-work-anniversary-card="1"', $html);
        $this->assertStringContainsString('data-work-anniversary-card="2"', $html);
        $this->assertStringNotContainsString('data-work-anniversary-card="3"', $html);
        $this->assertStringContainsString('5th Work Anniversary!', $html);
        $this->assertStringContainsString('1st Work Anniversary!', $html);
        $this->assertStringContainsString('Company Townhall', $html);
        $this->assertStringNotContainsString('Upcoming Only', $html);
    }

    public function test_drawer_shows_only_todays_work_anniversary_when_no_broadcasts_and_ignores_upcoming_only(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 11, 10, 0, 0));

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Current User', 'email' => 'current@example.com', 'is_active' => true, 'delete_status' => false],
            ['id' => 2, 'name' => 'Upcoming Anniversary', 'email' => 'upcoming@example.com', 'is_active' => true, 'delete_status' => false],
        ]);

        DB::table('user_details')->insert([
            ['user_id' => 1, 'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2024-11-12'],
            ['user_id' => 2, 'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2024-11-12'],
        ]);

        $authUser = User::query()->findOrFail(1);

        // Only upcoming work anniversaries exist -> count is 0 and empty state is rendered
        $this->actingAs($authUser)->getJson(route('broadcasts.drawer.count'))
            ->assertOk()
            ->assertJson([
                'count' => 0,
                'work_anniversary_count' => 0,
                'has_today_work_anniversary' => false,
            ]);

        $emptyHtml = $this->actingAs($authUser)->getJson(route('broadcasts.drawer.list', ['page' => 1]))
            ->assertOk()
            ->assertJson(['total' => 0, 'has_today_work_anniversary' => false])
            ->json('html');

        $this->assertStringContainsString('No Broadcasts Available', $emptyHtml);
        $this->assertStringNotContainsString('Upcoming Anniversary', $emptyHtml);

        // Add today's work anniversary for user 1 (2 years)
        DB::table('user_details')->where('user_id', 1)->update(['joining_date' => '2024-11-11']);

        $this->actingAs($authUser)->getJson(route('broadcasts.drawer.count'))
            ->assertOk()
            ->assertJson([
                'count' => 1,
                'work_anniversary_count' => 1,
                'has_today_work_anniversary' => true,
                'label' => '1 Broadcast',
            ]);

        $anniversaryOnlyHtml = $this->actingAs($authUser)->getJson(route('broadcasts.drawer.list', ['page' => 1]))
            ->assertOk()
            ->assertJson(['total' => 1, 'has_today_work_anniversary' => true])
            ->json('html');

        $this->assertStringContainsString('data-broadcast-section="current"', $anniversaryOnlyHtml);
        $this->assertStringNotContainsString('data-broadcast-section="upcoming"', $anniversaryOnlyHtml);
        $this->assertStringContainsString('2nd Work Anniversary!', $anniversaryOnlyHtml);
        $this->assertStringContainsString('(You)', $anniversaryOnlyHtml);
    }
}
