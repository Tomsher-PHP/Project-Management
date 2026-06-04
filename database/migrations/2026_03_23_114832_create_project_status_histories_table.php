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
        Schema::create('project_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('status_id')->constrained('project_statuses');
            $table->unsignedBigInteger('added_by')->nullable()->comment('user id')->index();
            $table->timestamp('added_at')->useCurrent();
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'status_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_status_histories');
    }
};
