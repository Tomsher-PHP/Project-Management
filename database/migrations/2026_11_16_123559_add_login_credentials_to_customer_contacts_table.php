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
        // Check for duplicate emails before creating the unique index.
        // The comparison also detects case and whitespace differences.
        $duplicates = DB::table('customer_contacts')
            ->selectRaw('LOWER(TRIM(email)) as normalized_email')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('email')
            ->groupByRaw('LOWER(TRIM(email))')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Duplicate customer contact emails found. ' .
                    'Resolve duplicates before running this migration: ' .
                    $duplicates->pluck('normalized_email')->implode(', ')
            );
        }

        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->boolean('login_enabled')->default(false)->after('is_active');
            $table->string('password', 255)->nullable()->after('login_enabled');

            $table->timestamp('password_set_at')->nullable()->after('password');
            $table->timestamp('last_login_at')->nullable()->after('password_set_at');
            $table->timestamp('email_verified_at')->nullable()->after('last_login_at');
            
            $table->boolean('force_password_reset')->default(false)->after('email_verified_at');

            $table->unique('email', 'customer_contacts_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->dropUnique('customer_contacts_email_unique');

            $table->dropColumn([
                'login_enabled',
                'password',
                'password_set_at',
                'last_login_at',
                'email_verified_at',
                'force_password_reset',
            ]);
        });
    }
};
