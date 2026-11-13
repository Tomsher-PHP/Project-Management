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
        Schema::create('daily_work_report_settings', function (Blueprint $table) {
            $table->id();

            $table->string('setting_key')->unique()->default('default');
            $table->string('report_key')->index();

            // Reports are disabled until an administrator enables them.
            $table->boolean('enabled')->default(false);

            // daily, weekdays, weekly
            $table->string('frequency', 20)->default('daily');

            $table->time('delivery_time')->default('00:00:00');
            $table->string('timezone', 100)->default('Asia/Dubai');

            // current_day, previous_working_day
            $table->string('reporting_period', 30)
                ->default('current_day');

            $table->boolean('send_zero_activity')->default(false);
            $table->boolean('include_inactive_users')->default(false);

            // Sections enabled in the generated report.
            $table->json('report_sections')->nullable();

            // Used when frequency is weekly: 1 = Monday, 7 = Sunday.
            $table->unsignedTinyInteger('weekly_day')->default(1);

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_work_report_settings');
    }
};
