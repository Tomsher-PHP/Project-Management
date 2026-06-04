<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('task_modes') || Schema::hasColumn('task_modes', 'customer_request')) {
            return;
        }

        Schema::table('task_modes', function (Blueprint $table) {
            $table->boolean('customer_request')->default(false)->after('track_performance');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('task_modes') || ! Schema::hasColumn('task_modes', 'customer_request')) {
            return;
        }

        Schema::table('task_modes', function (Blueprint $table) {
            $table->dropColumn('customer_request');
        });
    }
};
