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
        Schema::table('task_extend_time_requests', function (Blueprint $table) {
            $table->foreignId('cancelled_by')
                ->nullable()
                ->after('rejected_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')
                ->nullable()
                ->after('rejected_at');

            $table->text('cancellation_reason')
                ->nullable()
                ->after('rejection_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_extend_time_requests', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);

            $table->dropColumn([
                'cancelled_by',
                'cancelled_at',
                'cancellation_reason',
            ]);
        });
    }
};
