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
        Schema::create('task_schedules', function (Blueprint $table) {
            $table->id();

            // Task Information
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_milestone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_sprint_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_mode_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('current_assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('priority', 50)->default('medium');

            $table->unsignedBigInteger('estimated_time_seconds')->default(0);

            $table->boolean('is_billable')->default(false);

            // Schedule Settings
            $table->enum('frequency_type', [
                'daily',
                'weekdays',
                'weekly',
                'monthly',
            ]);

            $table->date('start_date');
            $table->date('end_date')->nullable();

            /**
             * selected_days
             * Example:
             * [1,2,3,4,5]
             */
            $table->json('week_days')->nullable();

            /**
             * weekly
             * Example:
             * 1 = Monday
             * 7 = Sunday
             */
            $table->unsignedTinyInteger('weekly_day')->nullable();

            /**
             * monthly
             * Example:
             * [5, 15] = Every month on 5th and 15th
             */
            $table->json('month_days')->nullable();

            // Generated Task Due Date
            $table->unsignedInteger('due_after_hours')->default(0);

            // Runtime Tracking
            $table->date('last_generated_for')->nullable();
            $table->timestamp('last_generated_at')->nullable();

            $table->boolean('is_active')->default(true);

            // Audit
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('frequency_type');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_schedules');
    }
};
