<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master settings tables that require a unique code column.
     *
     * @var array<int, string>
     */
    protected array $tables = [
        'departments',
        'designations',
        'technologies',
        'industries',
        'meeting_types',
        'meeting_locations',
        'meeting_tags',
        'broadcast_categories',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasColumn($tableName, 'code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('code', 20)->nullable()->unique()->after('name');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasColumn($tableName, 'code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropUnique(['code']);
                    $table->dropColumn('code');
                });
            }
        }
    }
};

