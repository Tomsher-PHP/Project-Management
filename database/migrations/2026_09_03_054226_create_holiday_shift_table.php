<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_shifts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('holiday_id')
                ->constrained('holidays')
                ->cascadeOnDelete();

            $table->foreignId('shift_id')
                ->constrained('shifts')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['holiday_id', 'shift_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_shifts');
    }
};
