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
        Schema::create('appraisal_snapshot_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_snapshot_category_id')->constrained(table: 'appraisal_snapshot_categories', indexName: 'fk_appr_snap_q_cat')->cascadeOnDelete();

            $table->text('question');
            $table->enum('question_type', ['rating', 'answer', 'target'])->default('rating');

            $table->enum('measurement_type', ['number', 'currency', 'percentage'])->nullable();
            $table->decimal('target_value', 15, 2)->nullable();
            $table->string('unit')->nullable();

            $table->unsignedInteger('sort_order')->default(1)->index('idx_sort_order');

            $table->timestamps();
            $table->softDeletes();

            $table->index('appraisal_snapshot_category_id', 'idx_appr_snap_cat_id');
            $table->index('question_type', 'idx_question_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_snapshot_questions');
    }
};
