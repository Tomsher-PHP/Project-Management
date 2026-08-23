<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('project_category_ids')->nullable()->after('domain');
        });

        $projects = DB::table('projects')->whereNotNull('project_category_id')->get();
        foreach ($projects as $project) {
            DB::table('projects')
                ->where('id', $project->id)
                ->update([
                    'project_category_ids' => json_encode([(int) $project->project_category_id]),
                ]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_category_id']);
            $table->dropColumn('project_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('project_category_id')->nullable()->after('domain')->constrained('project_categories')->nullOnDelete();
        });

        $projects = DB::table('projects')->whereNotNull('project_category_ids')->get();
        foreach ($projects as $project) {
            $ids = json_decode($project->project_category_ids, true);
            $firstId = (is_array($ids) && !empty($ids)) ? (int) $ids[0] : null;
            if ($firstId) {
                DB::table('projects')
                    ->where('id', $project->id)
                    ->update([
                        'project_category_id' => $firstId,
                    ]);
            }
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('project_category_ids');
        });
    }
};
