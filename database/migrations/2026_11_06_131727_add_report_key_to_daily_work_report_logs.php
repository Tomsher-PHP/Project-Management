<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_work_report_logs', function (Blueprint $table) {
            $table->string('report_key', 50)
                ->default('daily_work')
                ->after('report_type');
        });

        // Add the new index before dropping the old one.
        Schema::table('daily_work_report_logs', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'report_date', 'report_type', 'report_key'],
                'dwr_logs_user_date_type_key_unique'
            );
        });

        Schema::table('daily_work_report_logs', function (Blueprint $table) {
            $table->dropUnique('dwr_logs_user_date_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('daily_work_report_logs', function (Blueprint $table) {
            $table->dropUnique('dwr_logs_user_date_type_key_unique');

            $table->unique(
                ['user_id', 'report_date', 'report_type'],
                'dwr_logs_user_date_type_unique'
            );

            $table->dropColumn('report_key');
        });
    }
};
