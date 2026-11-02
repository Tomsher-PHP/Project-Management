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
        Schema::create('daily_work_report_deliveries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('daily_work_report_log_id')
                ->constrained('daily_work_report_logs')
                ->cascadeOnDelete();

            $table->string('recipient_email', 254);

            $table->string('recipient_name')->nullable();

            // employee, reporting_person, manager, global, team, user_specific
            $table->string('recipient_type', 30)->nullable();

            // Stores all applicable sources for this email.
            $table->json('recipient_sources')->nullable();

            // pending, queued, sent, failed
            $table->string('status', 20)->default('pending');

            $table->unsignedInteger('attempt_count')->default(0);

            $table->dateTime('sent_at')->nullable();

            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->unique(
                ['daily_work_report_log_id', 'recipient_email'],
                'dwr_delivery_log_email_unique'
            );

            $table->index(
                ['status', 'created_at'],
                'dwr_delivery_status_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_work_report_deliveries');
    }
};
