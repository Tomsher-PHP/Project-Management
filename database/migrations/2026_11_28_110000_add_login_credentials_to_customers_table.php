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
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('login_enabled')->default(false)->after('is_active');
            $table->string('username', 255)->nullable()->unique('customers_username_unique')->after('login_enabled');
            $table->string('password', 255)->nullable()->after('username');
            $table->timestamp('password_set_at')->nullable()->after('password');
            $table->timestamp('last_login_at')->nullable()->after('password_set_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_username_unique');

            $table->dropColumn([
                'login_enabled',
                'username',
                'password',
                'password_set_at',
                'last_login_at',
            ]);
        });
    }
};
