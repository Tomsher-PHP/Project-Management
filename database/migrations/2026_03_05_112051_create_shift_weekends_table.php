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
        Schema::create('shift_weekends', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();

            $table->tinyInteger('weekday')->comment('0-sun..6-sat');
            // 0 = Sunday, 1 = Monday ... 6 = Saturday

            $table->tinyInteger('week_number')->comment('1,2,3,4,5');
            // 1, 2, 3, 4, 5

            $table->timestamps();

            $table->unique(['shift_id', 'weekday', 'week_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_weekends');
    }
};
