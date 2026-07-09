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
            $table->foreignId('appraisal_snapshot_category_id')->constrained(table: 'appraisal_snapshot_categories', indexName: 'fk_app_snap_q_cat')->cascadeOnDelete();

            $table->text('question');
            $table->unsignedInteger('sort_order')->default(1);

            $table->timestamps();
            $table->softDeletes();
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
