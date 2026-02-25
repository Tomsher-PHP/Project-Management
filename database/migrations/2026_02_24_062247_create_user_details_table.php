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
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique();
            
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('designation_id')->nullable()->index();
            $table->unsignedBigInteger('reporter_id')->nullable()->index();
            $table->unsignedBigInteger('manager_id')->nullable()->index();
            
            $table->string('employee_id')->nullable()->unique();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_person_number')->nullable();

            $table->date('joining_date')->nullable();
            $table->date('leaving_date')->nullable();
            $table->date('dob')->nullable();
            $table->mediumText('address')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
