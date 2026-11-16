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
        // 1. Ensure project_notification_setting_id has an index so fk_pnsu_setting_id is satisfied
        if (!$this->hasIndex('project_notification_setting_users', 'pnsu_setting_id_idx')) {
            Schema::table('project_notification_setting_users', function (Blueprint $table) {
                $table->index('project_notification_setting_id', 'pnsu_setting_id_idx');
            });
        }

        // 2. Drop the foreign key on user_id dynamically (matching whatever name MySQL generated)
        $this->dropForeignIfExists('project_notification_setting_users', 'user_id');

        // 3. Drop unique constraint proj_notif_set_user_unique if it exists
        $this->dropIndexIfExists('project_notification_setting_users', 'proj_notif_set_user_unique');

        // 4. Alter user_id to nullable, re-add foreign key constraint with null on delete, and add email column
        Schema::table('project_notification_setting_users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            if (!Schema::hasColumn('project_notification_setting_users', 'email')) {
                $table->string('email', 255)->nullable()->after('user_id');
                $table->index('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove records where user_id is null before restoring NOT NULL constraint
        DB::table('project_notification_setting_users')->whereNull('user_id')->delete();

        $this->dropForeignIfExists('project_notification_setting_users', 'user_id');

        Schema::table('project_notification_setting_users', function (Blueprint $table) {
            if (Schema::hasColumn('project_notification_setting_users', 'email')) {
                $table->dropIndex(['email']);
                $table->dropColumn('email');
            }
        });

        Schema::table('project_notification_setting_users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['project_notification_setting_id', 'user_id'], 'proj_notif_set_user_unique');
        });

        $this->dropIndexIfExists('project_notification_setting_users', 'pnsu_setting_id_idx');
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
