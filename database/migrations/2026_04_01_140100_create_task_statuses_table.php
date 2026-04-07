<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('flow_type', ['agile', 'linear']);
            $table->string('code', 100);
            $table->string('color', 50)->nullable();
            $table->string('type', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['flow_type', 'code'], 'task_statuses_flow_type_code_unique');
            $table->index('flow_type');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_statuses');
    }
};
