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
        Schema::create('appraisal_reviewers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appraisal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('role', 50)->default('reporter');

            // Reporter 1, Reporter 2...
            $table->unsignedInteger('level')->default(1);

            $table->decimal('average_rating', 4, 2)->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->longText('acknowledgement_remark')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['appraisal_id', 'reviewer_user_id'], 'uq_appraisal_reviewer');

            $table->index('appraisal_id', 'idx_appraisal_id');
            $table->index('reviewer_user_id', 'idx_reviewer_user_id');
            $table->index(['appraisal_id', 'role'], 'idx_appraisal_role');
            $table->index(['appraisal_id', 'role', 'level'], 'idx_appraisal_role_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_reviewers');
    }
};
