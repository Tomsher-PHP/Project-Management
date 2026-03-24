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
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('project_type', ['agile', 'linear']);
            $table->string('priority')->nullable();
            $table->foreignId('status_id')->constrained('project_statuses');
            $table->string('project_stage')->nullable();

            // Dates
            $table->date('start_date')->nullable();
            $table->date('internal_end_date')->nullable();
            $table->date('client_end_date')->nullable();

            // Estimation
            $table->bigInteger('estimated_time_seconds')->nullable();

            // Extra
            $table->string('domain')->nullable();
            $table->longText('notes')->nullable();
            $table->foreignId('project_category_id')->nullable()->constrained()->nullOnDelete();

            // Billing
            $table->boolean('default_billable')->default(false);

            $table->boolean('status')->default(true);

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
