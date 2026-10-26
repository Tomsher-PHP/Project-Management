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
        Schema::create('project_timeline_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_timeline_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('from_status')->nullable();
            $table->tinyInteger('status');
            $table->unsignedBigInteger('added_by')->nullable()->comment('user id')->index();
            $table->timestamp('added_at')->useCurrent();
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index(['project_timeline_id', 'status'], 'ptsh_timeline_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_timeline_status_histories');
    }
};
