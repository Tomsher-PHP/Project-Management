<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_updates', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique();

            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->longText('instructions')->nullable();

            $table->string('feature_type')->default('new_feature');
            $table->string('module_name')->nullable();

            $table->string('icon')->nullable();
            $table->string('cover_image')->nullable();

            $table->json('screenshots')->nullable();
            $table->string('video_url')->nullable();

            // all, roles, users
            $table->string('target_type')->default('all');
            $table->json('target_roles')->nullable();
            $table->json('target_user_ids')->nullable();

            // normal, important, critical
            $table->string('priority')->default('normal');

            $table->boolean('is_mandatory')->default(false);

            // draft, published, archived
            $table->string('status')->default('draft');

            $table->dateTime('publish_at')->nullable();
            $table->dateTime('expires_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'publish_at']);
            $table->index(['module_name']);
            $table->index(['feature_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_updates');
    }
};
