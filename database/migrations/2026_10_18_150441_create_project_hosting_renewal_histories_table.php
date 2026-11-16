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
        Schema::create('project_hosting_renewal_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_hosting_record_id')
                ->constrained(
                    table: 'project_hosting_records',
                    indexName: 'phrh_record_fk'
                )
                ->restrictOnDelete();

            // Renewal information
            $table->date('renewal_date');
            $table->date('previous_start_date')->nullable();
            $table->date('previous_expiry_date')->nullable();
            $table->date('new_start_date')->nullable();
            $table->date('new_expiry_date');

            // Billing information at the time of renewal
            $table->foreignId('hosting_billing_cycle_id')
                ->nullable()
                ->constrained(
                    table: 'hosting_billing_cycles',
                    indexName: 'phrh_billing_fk'
                )
                ->nullOnDelete();

            $table->decimal('billing_amount', 12, 2)->nullable();
            $table->string('currency_code', 3)->nullable();

            // Renewal reference and notes
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            // User who processed the renewal

            $table->foreignId('renewed_by')
                ->nullable()
                ->constrained(
                    table: 'users',
                    indexName: 'phrh_user_fk'
                )
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['project_hosting_record_id', 'renewal_date'], 'hosting_renewal_record_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_hosting_renewal_histories');
    }
};
