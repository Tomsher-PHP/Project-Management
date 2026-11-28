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
        Schema::create('customer_password_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();
            $table->string('email', 255);
            $table->string('challenge_token_hash', 255)
                ->unique('customer_password_resets_challenge_token_hash_unique');
            $table->string('otp_hash', 255);
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('reset_token_hash', 255)->nullable();
            $table->timestamp('reset_expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();

            $table->index(
                ['customer_id', 'invalidated_at', 'used_at', 'expires_at'],
                'cpr_customer_active_idx'
            );
            $table->index('expires_at', 'cpr_expires_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_password_resets');
    }
};
