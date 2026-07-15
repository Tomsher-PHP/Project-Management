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
        Schema::create('appraisal_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_category_id')->constrained('appraisal_categories')->onDelete('cascade');

            $table->text('question');
            $table->enum('question_type', ['rating', 'answer', 'target'])->default('rating');

            $table->enum('measurement_type', ['number', 'currency', 'percentage'])->nullable();
            $table->decimal('target_value', 15, 2)->nullable();
            $table->string('unit')->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('appraisal_category_id', 'idx_appraisal_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_questions');
    }
};
