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
        Schema::create('appraisals', function (Blueprint $table) {
            $table->id();

            $table->year('year');
            $table->unsignedTinyInteger('month');

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('kpi_name', 255)->nullable();
            $table->longText('kpi_description')->nullable();
            $table->timestamp('kpi_agreed_at')->nullable();

            $table->enum('status', ['draft', 'published', 'completed', 'closed'])->default('draft');

            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['year', 'month', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisals');
    }
};
