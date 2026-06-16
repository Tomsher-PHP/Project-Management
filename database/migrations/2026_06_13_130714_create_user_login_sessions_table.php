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
        Schema::create('user_login_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Laravel session identifier
            $table->string('session_id', 255);

            // Login / Logout tracking
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            // Client information
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();

            // Device information
            $table->string('browser', 100)->nullable();
            $table->string('platform', 100)->nullable();
            $table->string('device', 100)->nullable();

            // Full browser user agent
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('session_id');
            $table->index('login_at');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_login_sessions');
    }
};
