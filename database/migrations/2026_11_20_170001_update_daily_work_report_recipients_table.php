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
        $existingIndexes = collect(Schema::getIndexes('daily_work_report_recipients'))->pluck('name')->all();

        Schema::table('daily_work_report_recipients', function (Blueprint $table) use ($existingIndexes) {
            if (!Schema::hasColumn('daily_work_report_recipients', 'report_setting_id')) {
                $table->unsignedBigInteger('report_setting_id')
                    ->nullable()
                    ->after('report_key');
            }

            if (!Schema::hasColumn('daily_work_report_recipients', 'user_id')) {
                $table->unsignedBigInteger('user_id')
                    ->nullable()
                    ->after('report_setting_id');
            }

            $table->string('scope', 20)->nullable()->change();

            if (!in_array('dwrr_setting_active_idx', $existingIndexes, true)) {
                $table->index(['report_setting_id', 'is_active'], 'dwrr_setting_active_idx');
            }

            if (!in_array('dwrr_user_id_idx', $existingIndexes, true)) {
                $table->index('user_id', 'dwrr_user_id_idx');
            }

            $table->foreign('report_setting_id', 'fk_dwrr_setting_id')
                ->references('id')
                ->on('daily_work_report_settings')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'fk_dwrr_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $existingIndexes = collect(Schema::getIndexes('daily_work_report_recipients'))->pluck('name')->all();

        Schema::table('daily_work_report_recipients', function (Blueprint $table) use ($existingIndexes) {
            $table->dropForeign('fk_dwrr_setting_id');
            $table->dropForeign('fk_dwrr_user_id');

            if (in_array('dwrr_setting_active_idx', $existingIndexes, true)) {
                $table->dropIndex('dwrr_setting_active_idx');
            }

            if (in_array('dwrr_user_id_idx', $existingIndexes, true)) {
                $table->dropIndex('dwrr_user_id_idx');
            }

            $columnsToDrop = array_filter(
                ['report_setting_id', 'user_id'],
                fn ($col) => Schema::hasColumn('daily_work_report_recipients', $col)
            );

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            // NOTE: Restoring the NOT NULL constraint on `scope` requires ensuring
            // any recipients with null scope have a valid scope assigned first;
            // never delete data automatically.
            $table->string('scope', 20)->nullable(false)->change();
        });
    }
};
