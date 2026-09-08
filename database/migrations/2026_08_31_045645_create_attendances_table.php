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
        Schema::create('attendances', function (Blueprint $table) {

            $table->id();

            /*
             * Employee
             */
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * Attendance date
             */
            $table->date('attendance_date');

            /*
             * Attendance status
             *
             * present
             * absent
             * half_day
             * leave
             * holiday
             * weekend
             * not_marked
             */
            $table->string('status', 30)
                ->default('not_marked');

            /*
             * Leave source
             *
             * reported = manually informed leave
             * approved = approved leave request
             */
            $table->string('leave_source', 30)
                ->nullable();

            /*
             * Related leave request.
             *
             * Nullable because an employee can be marked
             * as reported leave before submitting a leave request.
             */
            $table->foreignId('leave_request_id')
                ->nullable()
                ->constrained('leave_requests')
                ->nullOnDelete();

            /*
             * Attendance timings
             */
            $table->time('check_in')
                ->nullable();

            $table->time('check_out')
                ->nullable();

            /*
             * Total working hours.
             *
             * Example: 8.50
             */
            $table->decimal('working_hours', 5, 2)
                ->nullable();

            /*
             * Additional remarks
             */
            $table->text('remarks')
                ->nullable();

            /*
             * Person who marked/updated attendance
             */
            $table->foreignId('marked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
             * One attendance record per employee per day.
             */
            $table->unique([
                'user_id',
                'attendance_date',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
