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
        Schema::table('meetings', function (Blueprint $table) {
            $table->foreignId('rescheduled_from_id')->nullable()->after('location_details')->constrained('meetings')->nullOnDelete();
            $table->text('reschedule_reason')->nullable()->after('rescheduled_from_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['rescheduled_from_id']);
            $table->dropColumn(['rescheduled_from_id', 'reschedule_reason']);
        });
    }
};
