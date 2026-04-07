<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->foreignId('task_assignment_log_id')->nullable()->constrained('task_assignment_logs');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('duration_seconds')->default(0);
            $table->boolean('is_running')->default(true);
            $table->text('note')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('task_id');
            $table->index('user_id');
            $table->index('task_assignment_log_id');
            $table->index('is_running');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_time_logs');
    }
};
