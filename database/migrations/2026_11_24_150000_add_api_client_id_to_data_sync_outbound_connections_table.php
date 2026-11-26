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
        Schema::table('data_sync_outbound_connections', function (Blueprint $table) {
            $table->foreignId('api_client_id')
                ->nullable()
                ->after('id')
                ->constrained('api_clients')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_sync_outbound_connections', function (Blueprint $table) {
            $table->dropForeign(['api_client_id']);
            $table->dropColumn('api_client_id');
        });
    }
};

