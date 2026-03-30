<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('activitylog.database_connection');
        $tableName = config('activitylog.table_name');
        $schema = Schema::connection($connection);

        if (! $schema->hasTable($tableName)) {
            return;
        }

        $schema->table($tableName, function (Blueprint $table) use ($schema, $tableName) {
            if (! $schema->hasColumn($tableName, 'event')) {
                $table->string('event')->nullable()->after('subject_type');
            }

            if (! $schema->hasColumn($tableName, 'batch_uuid')) {
                $table->uuid('batch_uuid')->nullable()->after('properties');
            }
        });
    }

    public function down(): void
    {
        $connection = config('activitylog.database_connection');
        $tableName = config('activitylog.table_name');
        $schema = Schema::connection($connection);

        if (! $schema->hasTable($tableName)) {
            return;
        }

        $schema->table($tableName, function (Blueprint $table) use ($schema, $tableName) {
            $columnsToDrop = [];

            if ($schema->hasColumn($tableName, 'event')) {
                $columnsToDrop[] = 'event';
            }

            if ($schema->hasColumn($tableName, 'batch_uuid')) {
                $columnsToDrop[] = 'batch_uuid';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
