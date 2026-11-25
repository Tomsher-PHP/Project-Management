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
        if (! Schema::hasColumn('daily_work_report_logs', 'report_variant')) {
            Schema::table('daily_work_report_logs', function (Blueprint $table) {
                $table->string('report_variant', 30)
                    ->default('consolidated')
                    ->after('report_type');
            });
        }

        $indexes = collect(Schema::getIndexes('daily_work_report_logs'))
            ->pluck('name')
            ->all();

        // Create the replacement index before dropping the existing one.
        if (! in_array('dwrl_user_date_type_key_variant_unique', $indexes, true)) {
            Schema::table('daily_work_report_logs', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'report_date', 'report_type', 'report_key', 'report_variant'],
                    'dwrl_user_date_type_key_variant_unique'
                );
            });
        }

        // Drop the previous indexes after the replacement is available.
        $indexes = collect(Schema::getIndexes('daily_work_report_logs'))
            ->pluck('name')
            ->all();

        Schema::table('daily_work_report_logs', function (Blueprint $table) use ($indexes) {
            if (in_array('dwr_logs_user_date_type_key_unique', $indexes, true)) {
                $table->dropUnique('dwr_logs_user_date_type_key_unique');
            }

            if (in_array('dwrl_setting_date_type_key_unique', $indexes, true)) {
                $table->dropUnique('dwrl_setting_date_type_key_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('daily_work_report_logs'))
            ->pluck('name')
            ->all();

        // Restore the original unique index first.
        if (! in_array('dwr_logs_user_date_type_key_unique', $indexes, true)) {
            Schema::table('daily_work_report_logs', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'report_date', 'report_type', 'report_key'],
                    'dwr_logs_user_date_type_key_unique'
                );
            });
        }

        $indexes = collect(Schema::getIndexes('daily_work_report_logs'))
            ->pluck('name')
            ->all();

        Schema::table('daily_work_report_logs', function (Blueprint $table) use ($indexes) {
            if (in_array('dwrl_user_date_type_key_variant_unique', $indexes, true)) {
                $table->dropUnique('dwrl_user_date_type_key_variant_unique');
            }
        });

        if (Schema::hasColumn('daily_work_report_logs', 'report_variant')) {
            Schema::table('daily_work_report_logs', function (Blueprint $table) {
                $table->dropColumn('report_variant');
            });
        }
    }
};
