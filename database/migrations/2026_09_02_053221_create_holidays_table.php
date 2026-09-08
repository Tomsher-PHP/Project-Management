<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->date('from_date');
            $table->date('to_date');

            $table->text('description')->nullable();

            $table->boolean('is_public')->default(false);

            /*
             * all_users = Holiday applies to everyone
             * shift     = Holiday applies to selected shifts
             * user      = Holiday applies to selected users
             */
            $table->enum('applied_to', [
                'all_users',
                'shift',
                'user',
            ])->default('all_users');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['from_date', 'to_date']);
            $table->index('is_active');
            $table->index('is_public');
            $table->index('applied_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
