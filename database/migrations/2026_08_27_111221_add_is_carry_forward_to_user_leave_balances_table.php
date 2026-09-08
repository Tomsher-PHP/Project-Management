<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_leave_balances', function (Blueprint $table) {
            $table->boolean('is_carry_forward')
                ->default(false)
                ->after('carry_forward_balance');

            $table->index('is_carry_forward');
        });
    }

    public function down(): void
    {
        Schema::table('user_leave_balances', function (Blueprint $table) {
            $table->dropIndex([
                'user_leave_balances_is_carry_forward_index',
            ]);

            $table->dropColumn('is_carry_forward');
        });
    }
};
