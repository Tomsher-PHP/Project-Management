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
        $existingIndexes = collect(Schema::getIndexes('daily_work_report_logs'))->pluck('name')->all();

        Schema::table('daily_work_report_logs', function (Blueprint $table) use ($existingIndexes) {
            if (!Schema::hasColumn('daily_work_report_logs', 'report_setting_id')) {
                $table->unsignedBigInteger('report_setting_id')
                    ->nullable()
                    ->after('user_id');
            }

            if (!in_array('dwrl_setting_status_idx', $existingIndexes, true)) {
                $table->index(['report_setting_id', 'status'], 'dwrl_setting_status_idx');
            }

            if (!in_array('dwrl_setting_date_type_key_unique', $existingIndexes, true)) {
                $table->unique(
                    ['report_setting_id', 'report_date', 'report_type', 'report_key'],
                    'dwrl_setting_date_type_key_unique'
                );
            }

            $table->foreign('report_setting_id', 'fk_dwrl_setting_id')
                ->references('id')
                ->on('daily_work_report_settings')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $existingIndexes = collect(Schema::getIndexes('daily_work_report_logs'))->pluck('name')->all();

        Schema::table('daily_work_report_logs', function (Blueprint $table) use ($existingIndexes) {
            $table->dropForeign('fk_dwrl_setting_id');

            if (in_array('dwrl_setting_date_type_key_unique', $existingIndexes, true)) {
                $table->dropUnique('dwrl_setting_date_type_key_unique');
            }

            if (in_array('dwrl_setting_status_idx', $existingIndexes, true)) {
                $table->dropIndex('dwrl_setting_status_idx');
            }

            if (Schema::hasColumn('daily_work_report_logs', 'report_setting_id')) {
                $table->dropColumn('report_setting_id');
            }
        });
    }
};

