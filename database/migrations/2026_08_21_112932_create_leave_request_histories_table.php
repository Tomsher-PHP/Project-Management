<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_request_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('leave_request_id')
                ->constrained('leave_requests')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * submitted
             * approved
             * rejected
             * cancelled
             * date_changed
             * balance_deducted
             * balance_restored
             */
            $table->string('action');

            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();

            $table->date('old_from_date')->nullable();
            $table->date('old_to_date')->nullable();

            $table->date('new_from_date')->nullable();
            $table->date('new_to_date')->nullable();

            $table->text('reason')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_request_histories');
    }
};
