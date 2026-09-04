<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->decimal('paid_days', 8, 2)
                ->default(0)
                ->after('approved_duration');

            $table->decimal('unpaid_days', 8, 2)
                ->default(0)
                ->after('paid_days');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn([
                'paid_days',
                'unpaid_days',
            ]);
        });
    }
};
