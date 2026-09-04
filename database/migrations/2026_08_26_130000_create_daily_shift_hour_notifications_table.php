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
        Schema::create('daily_shift_hour_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_shift_assignment_id')->nullable()->constrained('user_shift_assignments')->nullOnDelete();
            $table->date('work_date');
            $table->string('notification_type')->default('short_hours');
            $table->unsignedInteger('required_seconds')->default(0);
            $table->unsignedInteger('worked_seconds')->default(0);
            $table->unsignedInteger('short_seconds')->default(0);
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'work_date', 'notification_type'], 'daily_shift_notif_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_shift_hour_notifications');
    }
};
