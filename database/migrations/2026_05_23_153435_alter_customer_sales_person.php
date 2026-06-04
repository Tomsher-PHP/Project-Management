<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('sales_person_id')
                ->nullable()
                ->after('company_address')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('customers')
            ->select('id', 'sales_person')
            ->orderBy('id')
            ->get()
            ->each(function ($customer) {
                $salesPersonId = filled($customer->sales_person)
                    ? DB::table('users')->where('name', $customer->sales_person)->value('id')
                    : null;

                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update(['sales_person_id' => $salesPersonId]);
            });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('sales_person');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('sales_person')->nullable()->after('company_address');
        });

        DB::table('customers')
            ->leftJoin('users', 'customers.sales_person_id', '=', 'users.id')
            ->select('customers.id', 'users.name')
            ->orderBy('customers.id')
            ->get()
            ->each(function ($customer) {
                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update(['sales_person' => $customer->name]);
            });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_person_id');
        });
    }
};
