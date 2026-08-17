<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('handoff_requests', function (Blueprint $table) {
            // Historical handoffs do not have an intended recipient.
            $table->foreignId('target_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['target_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('handoff_requests', function (Blueprint $table) {
            $table->dropIndex(['target_user_id', 'status']);
            $table->dropConstrainedForeignId('target_user_id');
        });
    }
};
