<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('project_module_id')->nullable()->constrained('project_modules');
            $table->foreignId('project_sprint_id')->nullable()->constrained('project_sprints');
            $table->foreignId('parent_task_id')->nullable()->constrained('project_tasks');

            $table->string('title');
            $table->string('code', 100)->unique();
            $table->longText('description')->nullable();

            $table->foreignId('status_id')->nullable()->constrained('project_task_statuses');

            $table->string('task_type', 50)->default('normal')->comment('behavior or nature');
            // sample task_type values: normal, quality, bug, test etc.
            $table->string('task_mode', 50)->default('standard')->comment('method of execution');
            // sample task_mode values: standard, rework, bug, improvement, research, etc.

            $table->string('priority', 50)->default('medium')->comment('low, medium, high, urgent');
            // sample priority values: low, medium, high, urgent.

            $table->unsignedBigInteger('current_assignee_id')->nullable();

            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
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
            $table->index('project_module_id');
            $table->index('project_sprint_id');
            $table->index('parent_task_id');
            $table->index('status_id');
            $table->index('current_assignee_id');
            $table->index('due_date');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
