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
        Schema::dropIfExists('appraisal_snapshot_questions1');

        Schema::table('appraisal_answers', function (Blueprint $table) {
            $table->dropForeign('fk_app_answer_question');
            $table->foreign('appraisal_snapshot_question_id', 'fk_app_answer_question')
                ->references('id')
                ->on('appraisal_snapshot_questions')
                ->cascadeOnDelete();
        });

        Schema::table('appraisal_snapshot_questions', function (Blueprint $table) {
            $table->foreign('appraisal_snapshot_category_id', 'fk_appr_snap_q_cat')
                ->references('id')
                ->on('appraisal_snapshot_categories')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisal_answers', function (Blueprint $table) {
            $table->dropForeign('fk_app_answer_question');
        });

        Schema::table('appraisal_snapshot_questions', function (Blueprint $table) {
            $table->dropForeign('fk_appr_snap_q_cat');
        });
    }
};
