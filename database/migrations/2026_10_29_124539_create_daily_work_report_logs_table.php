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
        Schema::create('daily_work_report_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('report_date');

            $table->dateTime('period_start');
            $table->dateTime('period_end');

            $table->string('report_type', 30)->default('daily');

            // pending, queued, sent, partial, failed, skipped
            $table->string('status', 20)->default('pending');

            // Snapshot of the report content at generation time.
            $table->json('report_data')->nullable();

            // Snapshot of resolved recipient addresses and their sources.
            $table->json('recipients_snapshot')->nullable();

            $table->unsignedInteger('attempt_count')->default(0);

            $table->text('last_error')->nullable();

            $table->dateTime('generated_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'report_date', 'report_type'],
                'dwr_logs_user_date_type_unique'
            );

            $table->index(
                ['report_date', 'status'],
                'dwr_logs_date_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_work_report_logs');
    }
};
