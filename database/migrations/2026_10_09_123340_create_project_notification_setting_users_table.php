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
        Schema::create('project_notification_setting_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_notification_setting_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('project_notification_setting_id', 'fk_pnsu_setting_id')
                  ->references('id')
                  ->on('project_notification_settings')
                  ->cascadeOnDelete();

            $table->unique(['project_notification_setting_id', 'user_id'], 'proj_notif_set_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_notification_setting_users');
    }
};
