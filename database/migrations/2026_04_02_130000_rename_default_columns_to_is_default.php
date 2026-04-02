<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that use default as a system/user-created boolean flag.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'departments',
        'designations',
        'technologies',
        'project_categories',
        'industries',
        'project_statuses',
        'project_stages',
        'agile_modules',
        'agile_sprints',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasColumn($tableName, 'default') || Schema::hasColumn($tableName, 'is_default')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('default', 'is_default');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasColumn($tableName, 'is_default') || Schema::hasColumn($tableName, 'default')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('is_default', 'default');
            });
        }
    }
};
