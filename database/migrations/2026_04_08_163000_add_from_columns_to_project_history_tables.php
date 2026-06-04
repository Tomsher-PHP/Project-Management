<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_status_histories', function (Blueprint $table) {
            $table->foreignId('from_status_id')
                ->nullable()
                ->after('project_id')
                ->constrained('project_statuses')
                ->nullOnDelete();
        });

        Schema::table('project_stage_histories', function (Blueprint $table) {
            $table->foreignId('from_stage_id')
                ->nullable()
                ->after('project_id')
                ->constrained('project_stages')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_status_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('from_status_id');
        });

        Schema::table('project_stage_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('from_stage_id');
        });
    }
};
