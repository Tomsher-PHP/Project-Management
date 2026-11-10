<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_work_report_recipients', function (Blueprint $table) {
            $table->string('report_key', 100)
                ->default('daily_work')
                ->after('id');

            $table->index(
                ['report_key', 'scope', 'scope_id', 'is_active'],
                'dwr_recipient_report_scope_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('daily_work_report_recipients', function (Blueprint $table) {
            $table->dropIndex('dwr_recipient_report_scope_idx');
            $table->dropColumn('report_key');
        });
    }
};
