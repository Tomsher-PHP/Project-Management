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
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')
                ->nullable()
                ->index()
                ->after('type');

            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->index()
                ->after('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn(['project_id', 'user_id']);
        });
    }
};
