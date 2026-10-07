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
        Schema::table('agile_sprints', function (Blueprint $table) {
            $table->foreignId('sprint_group_id')->nullable()->after('id')->constrained('sprint_groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agile_sprints', function (Blueprint $table) {
            $table->dropForeign(['sprint_group_id']);
            $table->dropColumn('sprint_group_id');
        });
    }
};
