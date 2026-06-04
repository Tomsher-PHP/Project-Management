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
        Schema::create('handoff_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            // Required only for agile projects. Keep nullable in DB and validate in service/request.
            $table->foreignId('project_milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();

            // Optional
            $table->foreignId('project_sprint_id')->nullable()->constrained('project_sprints')->nullOnDelete();

            // Optional task selected while creating the transfer request
            $table->foreignId('source_task_id')->nullable()->constrained('tasks')->nullOnDelete();

            // User who created the transfer request
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('purpose', 100)->comment('values of table handoff_purposes');
            $table->text('description');

            $table->tinyInteger('status')->default(0)->comment('0 = pending, 1 = noted, 2 = assigned');

            // Task created from this transfer request
            $table->foreignId('created_task_id')->nullable()->constrained('tasks')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('purpose');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handoff_requests');
    }
};
