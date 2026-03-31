<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('guard_name')->index();
        });
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $indexName = $tableNames['permissions'] . '_sort_order_index';

        Schema::table($tableNames['permissions'], function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
            $table->dropColumn('sort_order');
        });
    }
};
