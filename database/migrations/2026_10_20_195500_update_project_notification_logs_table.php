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
        if (!Schema::hasColumn('project_notification_logs', 'reminder_occasion')) {
            Schema::table('project_notification_logs', function (Blueprint $table) {
                $table->string('reminder_occasion', 50)->nullable()->after('scheduled_for');
            });
        }

        // Add unique constraint on (project_timeline_id, scheduled_for) if not already existing
        if (!$this->hasIndex('project_notification_logs', 'pnl_timeline_scheduled_unique')) {
            Schema::table('project_notification_logs', function (Blueprint $table) {
                $table->unique(['project_timeline_id', 'scheduled_for'], 'pnl_timeline_scheduled_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->hasIndex('project_notification_logs', 'pnl_timeline_scheduled_unique')) {
            Schema::table('project_notification_logs', function (Blueprint $table) {
                $table->dropUnique('pnl_timeline_scheduled_unique');
            });
        }

        if (Schema::hasColumn('project_notification_logs', 'reminder_occasion')) {
            Schema::table('project_notification_logs', function (Blueprint $table) {
                $table->dropColumn('reminder_occasion');
            });
        }
    }

    /**
     * Helper to check if an index exists on a table.
     */
    protected function hasIndex(string $table, string $indexName): bool
    {
        $dbName = DB::getDatabaseName();

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }
};
