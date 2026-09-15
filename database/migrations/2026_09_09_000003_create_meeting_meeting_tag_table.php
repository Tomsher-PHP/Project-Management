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
        Schema::create('meeting_meeting_tag', function (Blueprint $table) {
            $table->id();

            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->foreignId('meeting_tag_id')->constrained('meeting_tags')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['meeting_id', 'meeting_tag_id'], 'meeting_tag_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_meeting_tag');
    }
};
