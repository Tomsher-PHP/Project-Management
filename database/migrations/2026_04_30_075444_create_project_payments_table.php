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
        Schema::create('project_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 10, 2)->nullable();
            $table->date('paid_date')->nullable();

            $table->date('coverage_start_date')->default(now())->nullable();
            $table->date('coverage_end_date');

            $table->string('payment_method')->nullable(); // cash, bank, etc
            $table->string('reference')->nullable();      // txn id

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('added_by')->nullable()->comment('user id')->index();
            $table->timestamp('added_at')->useCurrent();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_payments');
    }
};
