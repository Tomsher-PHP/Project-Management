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
        Schema::create('user_shift_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot of shift details
            $table->string('shift_name');
            $table->time('time_from');
            $table->time('time_to');
            $table->unsignedInteger('break_duration')->default(0)->comment('in seconds');
            $table->string('color_code')->default('#6b7280');

            $table->date('date_from');
            $table->date('date_to')->nullable();
            // nullable = ongoing shift

            $table->string('reason')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'date_from', 'date_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_shift_assignments');
    }
};
