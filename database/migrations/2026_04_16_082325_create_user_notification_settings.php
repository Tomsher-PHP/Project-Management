<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');

            // config-driven values (no index needed)
            $table->string('action');

            $table->boolean('in_app')->default(1);
            $table->boolean('mail')->default(1);

            $table->timestamps();

            // FK
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // prevent duplicate settings per user/action/type
            $table->unique(['user_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_settings');
    }
};