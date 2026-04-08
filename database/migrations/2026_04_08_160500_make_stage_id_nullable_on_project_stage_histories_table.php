<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_stage_histories', function (Blueprint $table) {
            $table->dropForeign(['stage_id']);
        });

        Schema::table('project_stage_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('stage_id')->nullable()->change();
            $table->foreign('stage_id')->references('id')->on('project_stages')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_stage_histories', function (Blueprint $table) {
            $table->dropForeign(['stage_id']);
        });

        DB::table('project_stage_histories')->whereNull('stage_id')->delete();

        Schema::table('project_stage_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('stage_id')->nullable(false)->change();
            $table->foreign('stage_id')->references('id')->on('project_stages');
        });
    }
};
