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
        Schema::create('project_hosting_records', function (Blueprint $table) {
            $table->id();

            // Project relationship
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();

            // Hosting master-data relationships
            $table->foreignId('hosting_provider_id')->constrained('hosting_providers')->restrictOnDelete();

            $table->foreignId('hosting_service_id')->constrained('hosting_services')->restrictOnDelete();

            $table->foreignId('hosting_service_status_id')->constrained('hosting_service_statuses')->restrictOnDelete();

            $table->foreignId('hosting_billing_cycle_id')->nullable()->constrained('hosting_billing_cycles')->nullOnDelete();

            $table->foreignId('hosting_environment_id')->nullable()->constrained('hosting_environments')->nullOnDelete();

            // Hosting ownership
            $table->string('ownership_type', 20)->default('company')->comment('company or customer');

            // Hosting information
            $table->string('name');
            $table->string('domain')->nullable();
            $table->string('reference')->nullable();

            // Service dates
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('next_renewal_date')->nullable();
            $table->date('last_renewed_date')->nullable();

            // Billing information
            $table->decimal('billing_amount', 12, 2)->nullable();
            $table->string('currency_code', 3)->nullable();

            // Additional information
            $table->longText('description')->nullable();
            $table->text('notes')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(
                ['project_id', 'hosting_service_status_id'],
                'project_hosting_project_status_idx'
            );

            $table->index(
                ['project_id', 'ownership_type'],
                'project_hosting_ownership_idx'
            );

            $table->index('expiry_date');
            $table->index('next_renewal_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_hosting_records');
    }
};
