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
        Schema::create('data_sync_outbound_apis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('data_sync_outbound_connections')->restrictOnDelete();
            $table->string('module', 100);
            $table->string('operation', 50);
            $table->string('method', 10);
            $table->string('endpoint', 500);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['connection_id', 'module', 'operation'], 'dsoa_conn_mod_op_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_sync_outbound_apis');
    }
};

