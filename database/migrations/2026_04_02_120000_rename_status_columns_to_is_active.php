<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that use status as a boolean active/inactive flag.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'users',
        'roles',
        'departments',
        'designations',
        'attachments',
        'teams',
        'team_users',
        'shifts',
        'technologies',
        'project_categories',
        'industries',
        'customers',
        'customer_contacts',
        'project_statuses',
        'projects',
        'project_stages',
        'project_notes',
        'agile_modules',
        'agile_sprints',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasColumn($tableName, 'status') || Schema::hasColumn($tableName, 'is_active')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('status', 'is_active');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasColumn($tableName, 'is_active') || Schema::hasColumn($tableName, 'status')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('is_active', 'status');
            });
        }
    }
};
