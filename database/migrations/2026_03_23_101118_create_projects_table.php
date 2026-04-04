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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('project_code', 50)->unique();
            $table->string('name');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->enum('project_flow', ['agile', 'linear']);
            $table->string('priority', 50)->default('medium')->comment('low, medium, high, urgent');
            $table->foreignId('status_id')->constrained('project_statuses');
            $table->foreignId('project_stage_id')->nullable()->constrained('project_stages')->nullOnDelete();

            // Dates
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('customer_end_date')->nullable();

            // Estimation
            $table->unsignedBigInteger('estimated_time_seconds')->nullable();

            // Extra
            $table->string('domain')->nullable();
            $table->foreignId('project_category_id')->nullable()->constrained('project_categories')->nullOnDelete();

            // Billing
            $table->boolean('default_billable')->default(false);

            $table->boolean('is_active')->default(true);

            // Sales
            $table->foreignId('sales_person_id')->nullable()->constrained('users')->nullOnDelete();

            // Audit
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
