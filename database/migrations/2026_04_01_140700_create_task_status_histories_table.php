<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('status_id')->constrained('task_statuses');
            $table->unsignedBigInteger('added_by')->nullable()->comment('user id')->index();
            $table->timestamp('added_at')->useCurrent();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_status_histories');
    }
};
