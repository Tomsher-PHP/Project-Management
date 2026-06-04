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

        $teamRoles = array_keys(config('constants.team_roles'));

        Schema::create('team_user', function (Blueprint $table) use ($teamRoles) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->enum('team_role', $teamRoles);
            $table->dateTime('joined_at');

            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('added_by')->nullable()->comment('user id')->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};
