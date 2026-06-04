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
        Schema::create('project_checklist_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_checklist_id')->constrained('project_checklists')->cascadeOnDelete();

            $table->text('question');

            $table->integer('sort_order')->default(0);
            $table->tinyInteger('status')->default(0)->comment('0 = pending, 1 = completed');
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['project_checklist_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_checklist_items');
    }
};
