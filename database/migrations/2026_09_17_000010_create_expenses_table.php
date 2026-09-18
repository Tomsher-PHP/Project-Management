<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // Payment information
            $table->foreignId('payment_mode_id')->nullable()->constrained('expense_payment_modes')->nullOnDelete();
            $table->date('paid_date');
            $table->date('invoice_date')->nullable();

            $table->string('vat_payment', 20)->default('No VAT');
            $table->string('local_intl_payment', 20)->default('Local');

            $table->decimal('payment_amount', 15, 2);

            $table->string('other_currency', 10)->nullable();
            $table->decimal('other_amount', 15, 2)->nullable();

            $table->decimal('vat_amount', 15, 2)->default(0.00);
            $table->decimal('bank_charges', 15, 2)->default(0.00);

            // Invoice & provider information
            $table->string('invoice_number', 255)->nullable();
            $table->foreignId('service_provider_id')->nullable()->constrained('expense_service_providers')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->text('service_product')->nullable();

            // Customer & comment
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->mediumText('comment')->nullable();

            // Audit & status fields
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('payment_mode_id');
            $table->index('service_provider_id');
            $table->index('category_id');
            $table->index('vendor_id');
            $table->index('customer_id');
            $table->index('paid_date');
            $table->index('invoice_date');
            $table->index('invoice_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
