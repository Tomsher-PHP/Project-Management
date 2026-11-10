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
        Schema::create('broadcast_email_deliveries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('broadcast_id')->constrained('broadcasts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('email');
            $table->unsignedTinyInteger('status')->default(1)->comment('1-pending, 2-processing, 3-sent, 4-failed');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->unique(['broadcast_id', 'user_id']);
            $table->index(['broadcast_id', 'status']);
            $table->index(['status', 'last_attempt_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast_email_deliveries');
    }
};

