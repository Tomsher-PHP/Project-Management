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
        Schema::create('daily_work_report_recipients', function (Blueprint $table) {
            $table->id();

            // Identifies the report type, e.g. daily_work.
            $table->string('report_key', 100)
                ->default('daily_work');

            // global, team, user
            $table->string('scope', 20);

            // Null for global recipients.
            // Team ID for team recipients; user ID for user recipients.
            $table->unsignedBigInteger('scope_id')->nullable();

            $table->string('name')->nullable();
            $table->string('email', 254);

            $table->boolean('is_active')->default(true);

            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(
                ['scope', 'scope_id', 'is_active'],
                'dwr_recipients_scope_active_idx'
            );

            $table->index('email');

            $table->index(
                ['effective_from', 'effective_until'],
                'dwr_recipients_effective_dates_idx'
            );

            $table->index(
                ['report_key', 'scope', 'scope_id', 'is_active'],
                'dwr_recipient_report_scope_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_work_report_recipients');
    }
};
