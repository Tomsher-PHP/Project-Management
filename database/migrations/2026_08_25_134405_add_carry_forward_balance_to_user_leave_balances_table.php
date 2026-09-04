<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_leave_balances', function (Blueprint $table) {
            $table->decimal('carry_forward_balance', 8, 2)
                ->default(0)
                ->after('opening_balance');
        });
    }

    public function down(): void
    {
        Schema::table('user_leave_balances', function (Blueprint $table) {
            $table->dropColumn('carry_forward_balance');
        });
    }
};