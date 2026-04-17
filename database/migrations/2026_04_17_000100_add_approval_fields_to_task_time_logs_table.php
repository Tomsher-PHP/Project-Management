<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('task_time_logs')) {
            return;
        }

        Schema::table('task_time_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('task_time_logs', 'is_approved')) {
                $table->boolean('is_approved')->default(true)->after('note');
            }

            if (! Schema::hasColumn('task_time_logs', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('is_approved');
            }

            if (! Schema::hasColumn('task_time_logs', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        DB::table('task_time_logs')
            ->whereNull('is_approved')
            ->update([
                'is_approved' => true,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('task_time_logs')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('task_time_logs', 'is_approved') ? 'is_approved' : null,
            Schema::hasColumn('task_time_logs', 'approved_by') ? 'approved_by' : null,
            Schema::hasColumn('task_time_logs', 'approved_at') ? 'approved_at' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('task_time_logs', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
