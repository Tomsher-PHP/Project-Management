<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_general_settings', function (Blueprint $table) {
            $table->boolean('send_daily_work_report')
                ->default(true)
                ->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('user_general_settings', function (Blueprint $table) {
            $table->dropColumn('send_daily_work_report');
        });
    }
};
