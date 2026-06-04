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
        Schema::create('company_default_shifts', function (Blueprint $table) {
            $table->id();

            // Time Columns
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('break_duration')->default(0)->comment('seconds');

            // Working Days (Boolean Flags)
            $table->boolean('sunday')->default(false);
            $table->boolean('monday')->default(true);
            $table->boolean('tuesday')->default(true);
            $table->boolean('wednesday')->default(true);
            $table->boolean('thursday')->default(true);
            $table->boolean('friday')->default(true);
            $table->boolean('saturday')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_default_shifts');
    }
};
