<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_leave_balances', function (Blueprint $table) {
            $table->decimal('cancelled_days_restored', 8, 2)
                ->default(0)
                ->after('unpaid_days_used');
        });
    }

    public function down(): void
    {
        Schema::table('user_leave_balances', function (Blueprint $table) {
            $table->dropColumn('cancelled_days_restored');
        });
    }
};
