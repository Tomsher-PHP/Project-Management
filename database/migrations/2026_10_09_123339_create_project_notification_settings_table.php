<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type', 100)->unique();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('days_before');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('project_notification_settings')->insert([
            'notification_type' => 'timeline_ending_soon',
            'is_enabled' => true,
            'days_before' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_notification_settings');
    }
};
