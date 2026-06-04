<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('project_milestone_id')->nullable()->constrained('project_milestones');
            $table->foreignId('project_sprint_id')->nullable()->constrained('project_sprints');
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks');

            $table->string('name');
            $table->string('code', 100)->unique();
            $table->longText('description')->nullable();

            $table->foreignId('status_id')->nullable()->constrained('task_statuses');

            $table->foreignId('task_type_id')->nullable()->constrained('task_types')->nullOnDelete();
            $table->foreignId('task_mode_id')->nullable()->constrained('task_modes')->nullOnDelete();

            $table->string('priority', 50)->default('medium')->comment('low, medium, high, urgent');
            // sample priority values: low, medium, high, urgent.

            $table->foreignId('current_assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('due_date_time')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->unsignedBigInteger('estimated_time_seconds')->default(0);
            $table->unsignedBigInteger('derived_time_seconds')->default(0);
            $table->unsignedBigInteger('actual_time_seconds')->default(0);

            $table->boolean('is_billable')->default(false);
            $table->unsignedInteger('sort_order')->default(1);

            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('project_milestone_id');
            $table->index('project_sprint_id');
            $table->index('parent_task_id');
            $table->index('status_id');
            $table->index('task_type_id');
            $table->index('task_mode_id');
            $table->index('current_assignee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
