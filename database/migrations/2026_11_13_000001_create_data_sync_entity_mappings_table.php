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
        Schema::create('data_sync_entity_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained('api_clients')->cascadeOnDelete();
            $table->string('entity_type', 50);
            $table->string('external_code', 255)->nullable();
            $table->string('external_id', 255)->nullable();
            $table->unsignedBigInteger('local_id');
            $table->timestamps();

            $table->unique(['api_client_id', 'entity_type', 'external_code'], 'dsem_client_entity_code_unique');
            $table->unique(['api_client_id', 'entity_type', 'external_id'], 'dsem_client_entity_id_unique');
            $table->index(['api_client_id', 'entity_type', 'local_id'], 'dsem_client_entity_local_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_sync_entity_mappings');
    }
};

