<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_task_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_task_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_task_id', 'tag_id'], 'project_task_tags_unique');
            $table->index('tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_tags');
    }
};
