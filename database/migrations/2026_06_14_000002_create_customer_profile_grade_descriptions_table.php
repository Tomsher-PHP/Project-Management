<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profile_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profile_descriptions');
        Schema::dropIfExists('customer_profile_grade_descriptions');
    }
};
