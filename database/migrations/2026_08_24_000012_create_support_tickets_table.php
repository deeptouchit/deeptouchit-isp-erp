<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('ticket_no', 50)->unique();
                $table->string('subject', 255);
                $table->text('message');
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->enum('status', ['open', 'in_progress', 'waiting', 'answered', 'closed'])->default('open');
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'priority'], 'idx_tickets_status_priority');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
