<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_leave_balances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('year');

            $table->date('valid_from');
            $table->date('valid_to');

            $table->decimal('yearly_entitlement', 8, 2)->default(0);
            $table->decimal('monthly_entitlement', 8, 2)->default(0);

            $table->decimal('opening_balance', 8, 2)->default(0);
            $table->decimal('current_balance', 8, 2)->default(0);
            $table->decimal('used_balance', 8, 2)->default(0);

            $table->decimal('paid_days_used', 8, 2)->default(0);
            $table->decimal('unpaid_days_used', 8, 2)->default(0);

            $table->boolean('status')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['user_id', 'leave_type_id', 'year'],
                'user_leave_balance_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_leave_balances');
    }
};
