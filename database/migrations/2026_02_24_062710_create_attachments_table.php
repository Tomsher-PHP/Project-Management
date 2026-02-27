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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('link_id');
            $table->string('link_type')->comment('model name');

            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('file_size');

            $table->string('disk')->default('local')->comment('storage disk');
            $table->string('visibility')->default('private')->comment('private, public');
            $table->boolean('is_primary')->default(false);

            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('added_by')->nullable()->comment('user id')->index();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['link_id', 'link_type'], 'attachments_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
