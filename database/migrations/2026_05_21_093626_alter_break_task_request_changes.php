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
        // Add break request link to tables: tasks, task_time_logs
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('break_work_request_id')->nullable()->after('request_status')->constrained('break_work_requests')->nullOnDelete();
            
            DB::statement("ALTER TABLE tasks MODIFY request_type ENUM('self', 'assigned', 'break') NOT NULL DEFAULT 'assigned'");
        });

        Schema::table('task_time_logs', function (Blueprint $table) {
            $table->foreignId('break_work_request_id')->nullable()->after('approved_at')->constrained('break_work_requests')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_time_logs', function (Blueprint $table) {
            $table->dropForeign(['break_work_request_id']);
            $table->dropColumn('break_work_request_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            DB::statement("ALTER TABLE tasks MODIFY request_type ENUM('self', 'assigned') NOT NULL DEFAULT 'assigned'");

            $table->dropForeign(['break_work_request_id']);
            $table->dropColumn('break_work_request_id');
        });
    }
};
