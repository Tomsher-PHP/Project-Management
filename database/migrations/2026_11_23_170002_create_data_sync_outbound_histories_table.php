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
        Schema::create('data_sync_outbound_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_api_id')->constrained('data_sync_outbound_apis')->restrictOnDelete();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('request_url', 1000);
            $table->json('request_payload')->nullable();
            $table->unsignedInteger('response_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('outbound_api_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_sync_outbound_histories');
    }
};

