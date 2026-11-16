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
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->longText('content');

            $table->foreignId('broadcast_category_id')->constrained('broadcast_categories')->restrictOnDelete();

            $table->unsignedTinyInteger('priority')->default(3)->comment('1-urgent, 2-important, 3-normal');

            $table->string('audience_type', 20)->default('all')->comment('all, teams, users');

            $table->json('target_team_ids')->nullable()->comment('Selected team IDs for team-based allocation');

            $table->unsignedTinyInteger('status')->default(1)->comment('1-draft, 2-published, 3-archived');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->timestamp('published_at')->nullable();

            $table->foreignId('added_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('audience_type');
            $table->index(['start_date', 'end_date']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
