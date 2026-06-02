<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report_type')->index();
            $table->string('status')->index();
            $table->string('requested_via')->default('manual');
            $table->json('filters')->nullable();
            $table->string('disk', 50)->nullable();
            $table->string('path')->nullable();
            $table->string('filename')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
