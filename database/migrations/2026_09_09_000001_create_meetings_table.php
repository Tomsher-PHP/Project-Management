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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('meeting_type_id')->constrained('meeting_types');
            $table->foreignId('meeting_location_id')->nullable()->constrained('meeting_locations')->nullOnDelete();
            $table->foreignId('meeting_status_id')->constrained('meeting_statuses');
            $table->foreignId('organizer_id')->constrained('users');

            $table->string('title');
            $table->longText('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('url')->nullable();
            $table->text('location_details')->nullable();

            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('meeting_type_id');
            $table->index('meeting_location_id');
            $table->index('meeting_status_id');
            $table->index('organizer_id');
            $table->index(['start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
