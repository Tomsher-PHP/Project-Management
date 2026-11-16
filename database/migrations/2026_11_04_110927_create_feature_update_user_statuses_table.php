<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_update_user_statuses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('feature_update_id')
                ->constrained('feature_updates')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->dateTime('viewed_at')->nullable();
            $table->dateTime('acknowledged_at')->nullable();
            $table->dateTime('skipped_at')->nullable();
            $table->dateTime('last_viewed_at')->nullable();

            $table->unsignedInteger('view_count')->default(0);

            $table->timestamps();

            $table->unique(
                ['feature_update_id', 'user_id'],
                'feature_update_user_unique'
            );

            $table->index(['user_id', 'acknowledged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_update_user_statuses');
    }
};
