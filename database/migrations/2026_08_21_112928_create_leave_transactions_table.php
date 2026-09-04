<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->restrictOnDelete();

            $table->foreignId('leave_request_id')
                ->nullable()
                ->constrained('leave_requests')
                ->nullOnDelete();

            /*
             * deduction
             * restoration
             * adjustment
             */
            $table->string('transaction_type');

            $table->decimal('days', 8, 2);

            $table->decimal('balance_before', 8, 2);
            $table->decimal('balance_after', 8, 2);

            $table->decimal('paid_days', 8, 2)->default(0);
            $table->decimal('unpaid_days', 8, 2)->default(0);

            $table->date('transaction_date');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_transactions');
    }
};
