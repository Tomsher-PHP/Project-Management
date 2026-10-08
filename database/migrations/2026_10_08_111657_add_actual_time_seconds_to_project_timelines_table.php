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
        Schema::table('project_timelines', function (Blueprint $table) {
            $table->unsignedBigInteger('actual_time_seconds')->default(0)->after('customer_estimate_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_timelines', function (Blueprint $table) {
            $table->dropColumn('actual_time_seconds');
        });
    }
};
