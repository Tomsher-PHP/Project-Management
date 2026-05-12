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
        Schema::create('handoff_request_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('handoff_request_id')->constrained('handoff_requests')->cascadeOnDelete();

            // User who performed the action
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->tinyInteger('action')->default(0)->comment('0 = created, 1 = noted, 2 = assigned');

            $table->text('comment')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handoff_request_actions');
    }
};
