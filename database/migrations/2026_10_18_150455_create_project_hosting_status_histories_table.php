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
        Schema::create('project_hosting_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_hosting_record_id')
                ->constrained(
                    table: 'project_hosting_records',
                    indexName: 'phsh_record_fk'
                )
                ->restrictOnDelete();

            // Previous status; null when recording the initial status
            $table->foreignId('from_status_id')->nullable()->constrained('hosting_service_statuses')->restrictOnDelete();

            // New status
            $table->foreignId('to_status_id')->constrained('hosting_service_statuses')->restrictOnDelete();

            // When the status change occurred
            $table->dateTime('changed_at');

            // User who changed the status
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['project_hosting_record_id', 'changed_at'], 'hosting_status_record_date_idx');

            $table->index('to_status_id', 'hosting_status_to_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_hosting_status_histories');
    }
};
