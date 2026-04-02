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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->time('time_from');
            $table->time('time_to');

            $table->unsignedInteger('break_duration')->default(0)->comment('in seconds');
            $table->string('color_code')->default('#f3f4f6')->nullable();

            $table->boolean('is_default')->default(false)->comment('System default'); // only ONE shift should be default (handle in logic)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
