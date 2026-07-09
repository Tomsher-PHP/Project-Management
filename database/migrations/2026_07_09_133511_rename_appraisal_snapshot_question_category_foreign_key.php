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
        if (! Schema::hasTable('appraisal_snapshot_questions')) {
            return;
        }

        if (Schema::hasColumn('appraisal_snapshot_questions', 'appraisal_snapshot_categories_id')
            && ! Schema::hasColumn('appraisal_snapshot_questions', 'appraisal_snapshot_category_id')) {
            Schema::table('appraisal_snapshot_questions', function (Blueprint $table) {
                $table->dropForeign(['appraisal_snapshot_categories_id']);
            });

            Schema::table('appraisal_snapshot_questions', function (Blueprint $table) {
                $table->renameColumn('appraisal_snapshot_categories_id', 'appraisal_snapshot_category_id');
            });

            Schema::table('appraisal_snapshot_questions', function (Blueprint $table) {
                $table->foreign('appraisal_snapshot_category_id')
                    ->references('id')
                    ->on('appraisal_snapshot_categories')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('appraisal_snapshot_questions')) {
            return;
        }

        if (Schema::hasColumn('appraisal_snapshot_questions', 'appraisal_snapshot_category_id')
            && ! Schema::hasColumn('appraisal_snapshot_questions', 'appraisal_snapshot_categories_id')) {
            Schema::table('appraisal_snapshot_questions', function (Blueprint $table) {
                $table->dropForeign(['appraisal_snapshot_category_id']);
            });

            Schema::table('appraisal_snapshot_questions', function (Blueprint $table) {
                $table->renameColumn('appraisal_snapshot_category_id', 'appraisal_snapshot_categories_id');
            });

            Schema::table('appraisal_snapshot_questions', function (Blueprint $table) {
                $table->foreign('appraisal_snapshot_categories_id')
                    ->references('id')
                    ->on('appraisal_snapshot_categories')
                    ->cascadeOnDelete();
            });
        }
    }
};
