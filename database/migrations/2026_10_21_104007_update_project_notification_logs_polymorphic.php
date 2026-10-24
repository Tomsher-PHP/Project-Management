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
        // 1. Before changing table structure, truncate project_notification_logs safely with foreign key checks toggled.
        // NOTE: Existing notification logs are intentionally removed by this migration as requested.
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('project_notification_logs')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        // 2. Drop the foreign key on project_timeline_id if it exists
        $this->dropForeignIfExists('project_notification_logs', 'project_timeline_id');

        // 3. Drop the old unique index on (project_timeline_id, scheduled_for) if it exists
        $this->dropIndexIfExists('project_notification_logs', 'pnl_timeline_scheduled_unique');

        // 4. Drop the index on project_timeline_id if still present
        $this->dropIndexIfExists('project_notification_logs', 'project_notification_logs_project_timeline_id_foreign');
        $this->dropIndexIfExists('project_notification_logs', 'project_timeline_id');

        // 5. Update columns: add link_id & link_type, drop project_timeline_id
        Schema::table('project_notification_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('project_notification_logs', 'link_id')) {
                $table->unsignedBigInteger('link_id')->after('project_notification_setting_id');
            }
            if (!Schema::hasColumn('project_notification_logs', 'link_type')) {
                $table->string('link_type', 255)->after('link_id');
            }

            if (Schema::hasColumn('project_notification_logs', 'project_timeline_id')) {
                $table->dropColumn('project_timeline_id');
            }
        });

        // 6. Ensure reminder_occasion exists (VARCHAR 50)
        if (!Schema::hasColumn('project_notification_logs', 'reminder_occasion')) {
            Schema::table('project_notification_logs', function (Blueprint $table) {
                $table->string('reminder_occasion', 50)->nullable()->after('scheduled_for');
            });
        }

        // 7. Add unique index on (link_type, link_id, scheduled_for)
        if (!$this->hasIndex('project_notification_logs', 'pnl_link_scheduled_unique')) {
            Schema::table('project_notification_logs', function (Blueprint $table) {
                $table->unique(['link_type', 'link_id', 'scheduled_for'], 'pnl_link_scheduled_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('project_notification_logs', 'pnl_link_scheduled_unique');

        Schema::table('project_notification_logs', function (Blueprint $table) {
            if (Schema::hasColumn('project_notification_logs', 'link_type')) {
                $table->dropColumn('link_type');
            }
            if (Schema::hasColumn('project_notification_logs', 'link_id')) {
                $table->dropColumn('link_id');
            }

            if (!Schema::hasColumn('project_notification_logs', 'project_timeline_id')) {
                $table->foreignId('project_timeline_id')
                    ->nullable()
                    ->after('project_notification_setting_id')
                    ->constrained('project_timelines')
                    ->cascadeOnDelete();
            }
        });

        if (!$this->hasIndex('project_notification_logs', 'pnl_timeline_scheduled_unique') && Schema::hasColumn('project_notification_logs', 'project_timeline_id')) {
            Schema::table('project_notification_logs', function (Blueprint $table) {
                $table->unique(['project_timeline_id', 'scheduled_for'], 'pnl_timeline_scheduled_unique');
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

    /**
     * Helper to drop a foreign key on a specific column by looking up the actual constraint name in information_schema.
     */
    protected function dropForeignIfExists(string $table, string $column): void
    {
        $dbName = DB::getDatabaseName();

        $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    /**
     * Helper to drop an index if it exists on a table.
     */
    protected function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->hasIndex($table, $indexName)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }
    }
};
