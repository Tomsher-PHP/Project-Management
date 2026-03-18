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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->string('customer_code')->unique();
            $table->string('company_name');
            $table->string('company_email', 150)->nullable();

            $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('website')->nullable();
            $table->foreignId('registered_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('emirate')->nullable();

            $table->string('google_map_link')->nullable();
            $table->mediumText('company_address')->nullable();
            $table->string('sales_person')->nullable();

            $table->boolean('new_to_company')->default(true);
            $table->boolean('status')->default(true);

            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('emirate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('customers');
        Schema::enableForeignKeyConstraints();
    }
};
