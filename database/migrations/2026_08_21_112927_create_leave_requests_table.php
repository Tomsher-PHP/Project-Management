<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();

            /*
             * Employee who submitted the leave request.
             */
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * Leave Type
             * Example:
             * Annual Leave
             * Sick Leave
             * Casual Leave
             */
            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->restrictOnDelete();

            /*
             * Users assigned to review/approve the leave request.
             *
             * Contains the Reporting To and Manager user IDs.
             * Example: [5, 8]
             */
            $table->json('assigned_to')->nullable();

            /*
             * Leave duration type.
             *
             * full_day
             * half_day
             */
            $table->string('type')->default('full_day');

            /*
             * Dates requested by the employee.
             */
            $table->date('requested_from_date');
            $table->date('requested_to_date');

            /*
             * Automatically calculated requested duration.
             *
             * Examples:
             * Full day:
             * 3 days = 3.00
             *
             * Half day:
             * 1 half day = 0.50
             */
            $table->decimal('duration', 8, 2)->default(0);

            /*
             * Reason provided by employee.
             */
            $table->text('reason')->nullable();

            /*
             * Supporting document.
             */
            $table->string('attachment')->nullable();

            /*
             * Leave request status.
             *
             * pending
             * approved
             * rejected
             * cancelled
             */
            $table->string('status')
                ->default('pending')
                ->index();

            /*
             * Person who finally approved the request.
             *
             * This is different from the list of possible
             * managers/approvers.
             */
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Date/time when the request was submitted.
             *
             * This can be automatically populated when
             * the request is created.
             */
            $table->timestamp('submitted_at')
                ->nullable();

            /*
             * Final dates decided by the manager/approver.
             *
             * These are initially null.
             *
             * When the approver opens the request,
             * the requested dates can be loaded into the
             * approval form.
             *
             * If the approver does not change them,
             * the same requested dates are saved here.
             *
             * If the approver changes them,
             * the changed dates are saved here.
             */
            $table->date('approved_from_date')->nullable();
            $table->date('approved_to_date')->nullable();

            /*
             * Date/time when the request was approved.
             */
            $table->timestamp('approved_at')->nullable();

            /*
             * Date/time when the request was rejected.
             */
            $table->timestamp('rejected_at')->nullable();

            /*
             * Rejection information.
             */
            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Cancellation information.
             *
             * A request can be cancelled before or after approval.
             */
            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            /*
             * Indexes.
             */
            $table->index('user_id');
            $table->index('leave_type_id');
            $table->index('approved_by');
            $table->index('cancelled_by');
            $table->index([
                'requested_from_date',
                'requested_to_date'
            ]);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
