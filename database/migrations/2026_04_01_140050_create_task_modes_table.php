<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_modes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 100)->unique();
            $table->text('description')->nullable();
            $table->string('color', 20)->nullable();
            $table->boolean('is_productive')->default(true);
            $table->boolean('is_rework')->default(false);
            $table->boolean('track_performance')->default(true);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('is_system');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_modes');
    }
};
