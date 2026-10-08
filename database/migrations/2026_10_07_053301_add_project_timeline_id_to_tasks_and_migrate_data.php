<?php

use App\Models\ProjectTimeline;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Correct status default
        Schema::table('project_timelines', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1)->change();
        });

        // 2. Add nullable project_timeline_id to tasks
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_timeline_id')->nullable()->constrained('project_timelines')->after('project_id');
        });

        // 3. Migrate data
        $projects = DB::table('projects')->get();

        foreach ($projects as $project) {
            $timelineId = DB::table('project_timelines')->insertGetId([
                'project_id' => $project->id,
                'name' => 'Original',
                'type' => 'original',
                'status' => ProjectTimeline::STATUS_ACTIVE,
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,
                'customer_end_date' => $project->customer_end_date,
                'estimated_time_seconds' => $project->estimated_time_seconds,
                'customer_estimate_seconds' => $project->customer_estimate_seconds,
                'sort_order' => 0,
                'notes' => null,
                'created_by' => $project->added_by ?? null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => $project->deleted_at,
            ]);

            DB::table('tasks')
                ->where('project_id', $project->id)
                ->update(['project_timeline_id' => $timelineId]);
        }

        // 4. Data integrity verification
        $tasksWithoutTimeline = DB::table('tasks')->whereNull('project_timeline_id')->count();
        if ($tasksWithoutTimeline > 0) {
            throw new \Exception("Data integrity failure: {$tasksWithoutTimeline} tasks remain without a project_timeline_id.");
        }

        $invalidTasks = DB::table('tasks')
            ->join('project_timelines', 'tasks.project_timeline_id', '=', 'project_timelines.id')
            ->whereColumn('tasks.project_id', '!=', 'project_timelines.project_id')
            ->count();

        if ($invalidTasks > 0) {
            throw new \Exception("Data integrity failure: {$invalidTasks} tasks belong to a timeline from a different project.");
        }

        // 5. Make project_timeline_id required
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_timeline_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['project_timeline_id']);
            $table->dropColumn('project_timeline_id');
        });

        Schema::table('project_timelines', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(0)->change();
        });

        DB::table('project_timelines')->truncate();
    }
};
