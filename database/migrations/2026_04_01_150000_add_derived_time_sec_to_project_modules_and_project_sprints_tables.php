<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_modules', function (Blueprint $table) {
            $table->unsignedBigInteger('derived_time_sec')->default(0)->after('estimated_time_seconds');
        });

        Schema::table('project_sprints', function (Blueprint $table) {
            $table->unsignedBigInteger('derived_time_sec')->default(0)->after('estimated_time_seconds');
        });

        DB::table('project_sprints')->update([
            'derived_time_sec' => 0,
        ]);

        $moduleDerivedTimes = DB::table('project_sprints')
            ->select('project_module_id', DB::raw('COALESCE(SUM(estimated_time_seconds), 0) as derived_total'))
            ->whereNull('deleted_at')
            ->groupBy('project_module_id')
            ->pluck('derived_total', 'project_module_id');

        DB::table('project_modules')->update([
            'derived_time_sec' => 0,
        ]);

        foreach ($moduleDerivedTimes as $projectModuleId => $derivedTotal) {
            DB::table('project_modules')
                ->where('id', $projectModuleId)
                ->update(['derived_time_sec' => (int) $derivedTotal]);
        }
    }

    public function down(): void
    {
        Schema::table('project_sprints', function (Blueprint $table) {
            $table->dropColumn('derived_time_sec');
        });

        Schema::table('project_modules', function (Blueprint $table) {
            $table->dropColumn('derived_time_sec');
        });
    }
};
