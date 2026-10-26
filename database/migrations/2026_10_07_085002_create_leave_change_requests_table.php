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
        Schema::create('leave_change_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('leave_request_id')
                ->constrained('leave_requests')
                ->cascadeOnDelete();

            $table->foreignId('requested_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->json('assigned_to')->nullable();

            // Existing approved leave
            $table->foreignId('old_leave_type_id')
                ->constrained('leave_types');

            $table->date('old_from_date');
            $table->date('old_to_date');
            $table->decimal('old_duration', 8, 2);
            $table->string('old_day_type')->nullable();
            $table->string('old_half_day_type')->nullable();

            // Requested new values
            $table->foreignId('new_leave_type_id')
                ->constrained('leave_types');

            $table->date('new_from_date');
            $table->date('new_to_date');
            $table->decimal('new_duration', 8, 2);
            $table->string('new_day_type')->nullable();
            $table->string('new_half_day_type')->nullable();

            $table->text('reason')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancelled',
            ])->default('pending');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('rejected_at')->nullable();

            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_change_requests');
    }
};
