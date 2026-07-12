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
        Schema::table('appraisal_questions', function (Blueprint $table) {
            $table->enum('question_type', ['rating', 'answer'])->default('rating')->after('question');
        });

        Schema::table('appraisal_snapshot_questions', function (Blueprint $table) {
            $table->enum('question_type', ['rating', 'answer'])->default('rating')->after('question');
        });

        Schema::table('appraisal_answers', function (Blueprint $table) {
            $table->longText('assignee_answer')->nullable()->after('assignee_remark');
        });

        Schema::table('appraisals', function (Blueprint $table) {
            $table->decimal('assignee_average_rating', 3, 2)->nullable()->after('status');
            $table->decimal('reporter_average_rating', 3, 2)->nullable()->after('assignee_average_rating');
            $table->decimal('manager_average_rating', 3, 2)->nullable()->after('reporter_average_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisal_questions', function (Blueprint $table) {
            $table->dropColumn('question_type');
        });

        Schema::table('appraisal_snapshot_questions', function (Blueprint $table) {
            $table->dropColumn('question_type');
        });

        Schema::table('appraisal_answers', function (Blueprint $table) {
            $table->dropColumn('assignee_answer');
        });

        Schema::table('appraisals', function (Blueprint $table) {
            $table->dropColumn([
                'assignee_average_rating',
                'reporter_average_rating',
                'manager_average_rating',
            ]);
        });
    }
};
