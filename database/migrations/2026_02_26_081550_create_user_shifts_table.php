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
        Schema::create('user_shifts', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Shift Time
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('break_duration')->default(0)->comment('seconds');

            // Effective Period
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            $table->unsignedBigInteger('added_by')->nullable()->comment('user id')->index();
            $table->unsignedBigInteger('updated_by')->nullable()->comment('user id')->index();

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'start_time']);
            $table->index(['effective_from', 'effective_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_shifts');
    }
};
