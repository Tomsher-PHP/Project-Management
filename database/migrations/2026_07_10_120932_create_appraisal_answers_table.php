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

            // Assignee
            $table->decimal('assignee_rating', 2, 1)->nullable();            
            $table->longText('assignee_remark')->nullable();
            $table->timestamp('assignee_submitted_at')->nullable();
            
            // Reporter
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('reporter_rating', 2, 1)->nullable();
            $table->longText('reporter_remark')->nullable();
            $table->timestamp('reporter_submitted_at')->nullable();
            
            // Manager
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('manager_rating', 2, 1)->nullable();
            $table->longText('manager_remark')->nullable();
            $table->timestamp('manager_submitted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['appraisal_id', 'appraisal_snapshot_question_id'], 'uq_appraisal_question');
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
