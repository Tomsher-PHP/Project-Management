<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $existingIndexes = collect(Schema::getIndexes('daily_work_report_settings'))->pluck('name')->all();

        Schema::table('daily_work_report_settings', function (Blueprint $table) use ($existingIndexes) {
            if (!Schema::hasColumn('daily_work_report_settings', 'report_name')) {
                $table->string('report_name', 255)->nullable()->after('setting_key');
            }

            if (!Schema::hasColumn('daily_work_report_settings', 'settings')) {
                $table->json('settings')->nullable()->after('report_sections');
            }

            // Remove unique constraint on report_key to support multiple configurations
            // for the same report type (e.g. multiple project reports).
            // Handles both the original auto-generated name and shortened rollback name.
            if (in_array('dwrs_report_key_unique', $existingIndexes, true)) {
                $table->dropUnique('dwrs_report_key_unique');
            } elseif (in_array('daily_work_report_settings_report_key_unique', $existingIndexes, true)) {
                $table->dropUnique('daily_work_report_settings_report_key_unique');
            }

            // Add short-named composite index on (report_key, enabled).
            if (!in_array('dwrs_report_enabled_idx', $existingIndexes, true)) {
                $table->index(['report_key', 'enabled'], 'dwrs_report_enabled_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $existingIndexes = collect(Schema::getIndexes('daily_work_report_settings'))->pluck('name')->all();

        Schema::table('daily_work_report_settings', function (Blueprint $table) use ($existingIndexes) {
            if (in_array('dwrs_report_enabled_idx', $existingIndexes, true)) {
                $table->dropIndex('dwrs_report_enabled_idx');
            }

            $columnsToDrop = array_filter(
                ['report_name', 'settings'],
                fn ($col) => Schema::hasColumn('daily_work_report_settings', $col)
            );

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            // NOTE: Restoring the unique `report_key` constraint requires removing or deduplicating
            // duplicate report keys first; never delete data automatically.
            if (!in_array('dwrs_report_key_unique', $existingIndexes, true) &&
                !in_array('daily_work_report_settings_report_key_unique', $existingIndexes, true)) {
                $table->unique('report_key', 'dwrs_report_key_unique');
            }
        });
    }
};
