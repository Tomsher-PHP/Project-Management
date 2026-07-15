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
        Schema::create('appraisal_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appraisal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appraisal_reviewer_id')->constrained('appraisal_reviewers')->cascadeOnDelete();

            $table->longText('comment');

            $table->timestamps();
            $table->softDeletes();

            $table->index('appraisal_id');
            $table->index('appraisal_reviewer_id');
            $table->unique(['appraisal_id','appraisal_reviewer_id'], 'uq_appraisal_reviewer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_comments');
    }
};
