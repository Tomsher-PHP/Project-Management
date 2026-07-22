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
        Schema::create('appraisal_answer_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appraisal_answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appraisal_reviewer_id')->constrained('appraisal_reviewers')->cascadeOnDelete();

            $table->decimal('rating', 2, 1)->nullable();

            $table->longText('remark')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['appraisal_answer_id','appraisal_reviewer_id'], 'uq_appraisal_answer_reviewer');
            $table->index('appraisal_answer_id', 'idx_appraisal_answer_id');
            $table->index('appraisal_reviewer_id', 'idx_appraisal_reviewer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_answer_reviews');
    }
};
