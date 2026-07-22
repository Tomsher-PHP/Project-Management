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
        Schema::create('appraisal_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appraisal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appraisal_snapshot_question_id')->constrained(table: 'appraisal_snapshot_questions', indexName: 'fk_app_answer_question')->cascadeOnDelete();

            // rating type question
            $table->decimal('rating', 2, 1)->nullable();

            // answer type question
            $table->longText('answer')->nullable();

            // target type question
            $table->decimal('achieved_value', 15, 2)->nullable();
            $table->decimal('achievement_percentage', 8, 2)->nullable();

            // common remark
            $table->longText('remark')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['appraisal_id', 'appraisal_snapshot_question_id'], 'uq_appraisal_question');
            $table->index('appraisal_id');
            $table->index('appraisal_snapshot_question_id', 'idx_appraisal_question');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_answers');
    }
};
