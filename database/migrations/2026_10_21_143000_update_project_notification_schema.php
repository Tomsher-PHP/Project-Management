<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. First, truncate the 3 explicitly authorized tables before altering schema
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            DB::table('project_notification_logs')->truncate();
            DB::table('project_notification_setting_users')->truncate();
            DB::table('project_notification_settings')->truncate();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        // 2. Remove days_before from project_notification_settings
        Schema::table('project_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('project_notification_settings', 'days_before')) {
                $table->dropColumn('days_before');
            }
        });

        // 3. Create project_notification_setting_days
        if (!Schema::hasTable('project_notification_setting_days')) {
            Schema::create('project_notification_setting_days', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_notification_setting_id');
                $table->unsignedInteger('days_before');
                $table->timestamps();

                $table->foreign('project_notification_setting_id', 'fk_pnsd_setting_id')
                    ->references('id')
                    ->on('project_notification_settings')
                    ->cascadeOnDelete();

                $table->unique(
                    ['project_notification_setting_id', 'days_before'],
                    'pnsd_setting_days_unique'
                );
            });
        }

        // 4. Create project_notification_defaults
        if (!Schema::hasTable('project_notification_defaults')) {
            Schema::create('project_notification_defaults', function (Blueprint $table) {
                $table->id();
                $table->string('setting_key', 100)->index();
                $table->json('conditions');
                $table->json('setting_value');
                $table->timestamps();
            });
        }

        // 5. Create project_notification_preferences
        if (!Schema::hasTable('project_notification_preferences')) {
            Schema::create('project_notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')
                    ->constrained('projects')
                    ->cascadeOnDelete();
                $table->string('notification_type', 100)->index();
                $table->boolean('is_enabled');
                $table->timestamps();

                $table->unique(
                    ['project_id', 'notification_type'],
                    'pnp_project_type_unique'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_notification_preferences');
        Schema::dropIfExists('project_notification_defaults');
        Schema::dropIfExists('project_notification_setting_days');

        Schema::table('project_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('project_notification_settings', 'days_before')) {
                $table->unsignedInteger('days_before')->after('is_enabled')->default(0);
            }
        });
    }
};
