<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_report_preferences', function (Blueprint $table) {
            $table->boolean('receive_individual_report')
                ->default(true)
                ->after('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('user_report_preferences', function (Blueprint $table) {
            $table->dropColumn('receive_individual_report');
        });
    }
};
