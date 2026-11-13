<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_work_report_settings', function (Blueprint $table) {
            $table->string('report_key', 100)
                ->default('daily_work')
                ->after('setting_key');

            $table->unique('report_key');
        });
    }

    public function down(): void
    {
        Schema::table('daily_work_report_settings', function (Blueprint $table) {
            $table->dropUnique(['report_key']);
            $table->dropColumn('report_key');
        });
    }
};
