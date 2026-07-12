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
            $table->foreignId('appraisal_id')->constrained('appraisals')->onDelete('cascade');
            $table->enum('role', ['reporter', 'manager']);
            $table->foreignId('commented_by')->constrained('users')->onDelete('cascade');
            $table->longText('comment');
            $table->timestamps();
            $table->softDeletes();

            $table->index('appraisal_id');
            $table->index('commented_by');
            $table->unique(['appraisal_id', 'role']);
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
