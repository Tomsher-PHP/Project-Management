<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'request_type')) {
                $table->enum('request_type', ['self', 'assigned'])->default('assigned')->after('is_billable');
            }

            if (! Schema::hasColumn('tasks', 'request_status')) {
                $table->enum('request_status', ['pending', 'approved', 'rejected'])->default('approved')->after('request_type');
            }

            if (! Schema::hasColumn('tasks', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('request_status');
            }

            if (! Schema::hasColumn('tasks', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('tasks', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_at');
            }

            if (! Schema::hasColumn('tasks', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }

            if (! Schema::hasColumn('tasks', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
        });

        DB::table('tasks')
            ->whereNull('request_status')
            ->update([
                'request_type' => 'assigned',
                'request_status' => 'approved',
            ]);

        if (! $this->hasRequestStatusIndex()) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->index(['request_type', 'request_status'], 'tasks_request_type_request_status_index');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        if ($this->hasRequestStatusIndex()) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropIndex('tasks_request_type_request_status_index');
            });
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('tasks', 'request_type') ? 'request_type' : null,
            Schema::hasColumn('tasks', 'request_status') ? 'request_status' : null,
            Schema::hasColumn('tasks', 'approved_by') ? 'approved_by' : null,
            Schema::hasColumn('tasks', 'approved_at') ? 'approved_at' : null,
            Schema::hasColumn('tasks', 'rejected_by') ? 'rejected_by' : null,
            Schema::hasColumn('tasks', 'rejected_at') ? 'rejected_at' : null,
            Schema::hasColumn('tasks', 'rejection_reason') ? 'rejection_reason' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    private function hasRequestStatusIndex(): bool
    {
        $table = DB::getTablePrefix() . 'tasks';
        $indexName = 'tasks_request_type_request_status_index';

        return collect(DB::select("SHOW INDEX FROM {$table}"))
            ->contains(fn ($index) => ($index->Key_name ?? null) === $indexName);
    }
};
