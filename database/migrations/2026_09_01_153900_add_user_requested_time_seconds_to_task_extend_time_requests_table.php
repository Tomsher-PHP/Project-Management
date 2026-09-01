<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('task_extend_time_requests', function (Blueprint $table) {
            $table->integer('user_requested_time_seconds')
                ->nullable()
                ->after('new_estimated_time_seconds')
                ->comment('in seconds');
        });

        DB::table('task_extend_time_requests')
            ->whereNull('user_requested_time_seconds')
            ->update([
                'user_requested_time_seconds' => DB::raw('new_estimated_time_seconds'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_extend_time_requests', function (Blueprint $table) {
            $table->dropColumn('user_requested_time_seconds');
        });
    }
};
