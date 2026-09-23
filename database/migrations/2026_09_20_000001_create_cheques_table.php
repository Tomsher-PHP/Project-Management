<?php

use App\Models\Cheque;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();

            $table->string('cheque_number', 255);
            $table->decimal('amount', 15, 2);
            $table->date('cheque_date');
            $table->date('cheque_given');
            $table->string('cheque_to', 255);
            $table->text('purpose');
            $table->string('cheque_status', 50)->default(Cheque::STATUS_PENDING);
            $table->date('debited_date')->nullable();

            // Audit fields
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('cheque_number');
            $table->index('cheque_date');
            $table->index('cheque_given');
            $table->index('cheque_status');
            $table->index('debited_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
