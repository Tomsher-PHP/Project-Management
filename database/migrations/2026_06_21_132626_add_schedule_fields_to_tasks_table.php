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
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('task_schedule_id')->nullable()->after('parent_task_id')->constrained('task_schedules')->nullOnDelete();
            $table->date('scheduled_for_date')->nullable()->after('task_schedule_id');

            $table->index([
                'task_schedule_id',
                'scheduled_for_date',
            ], 'tasks_schedule_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_schedule_date_idx');
            $table->dropConstrainedForeignId('task_schedule_id');
            $table->dropColumn('scheduled_for_date');
        });
    }
};
