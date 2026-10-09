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
        Schema::create('project_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_notification_setting_id');
            $table->foreignId('project_timeline_id')->constrained('project_timelines')->cascadeOnDelete();
            $table->date('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('project_notification_setting_id', 'fk_pnl_setting_id')
                  ->references('id')
                  ->on('project_notification_settings')
                  ->cascadeOnDelete();

            $table->unique(['project_notification_setting_id', 'project_timeline_id'], 'proj_notif_set_timeline_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_notification_logs');
    }
};
