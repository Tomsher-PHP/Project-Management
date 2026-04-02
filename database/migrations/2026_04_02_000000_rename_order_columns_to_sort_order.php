<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
        'project_modules',
        'project_sprints',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'order')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('order', 'sort_order');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'sort_order')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('sort_order', 'order');
            });
        }
    }
};
