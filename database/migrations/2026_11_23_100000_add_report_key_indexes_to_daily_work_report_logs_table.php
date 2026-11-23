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
            if (!in_array('dwrl_report_key_id_idx', $existingIndexes, true)) {
                $table->index(['report_key', 'id'], 'dwrl_report_key_id_idx');
            }

            if (!in_array('dwrl_report_key_date_id_idx', $existingIndexes, true)) {
                $table->index(['report_key', 'report_date', 'id'], 'dwrl_report_key_date_id_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $existingIndexes = collect(Schema::getIndexes('daily_work_report_logs'))->pluck('name')->all();

        Schema::table('daily_work_report_logs', function (Blueprint $table) use ($existingIndexes) {
            if (in_array('dwrl_report_key_date_id_idx', $existingIndexes, true)) {
                $table->dropIndex('dwrl_report_key_date_id_idx');
            }

            if (in_array('dwrl_report_key_id_idx', $existingIndexes, true)) {
                $table->dropIndex('dwrl_report_key_id_idx');
            }
        });
    }
};

