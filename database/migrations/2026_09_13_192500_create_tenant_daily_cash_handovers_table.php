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
        if (!Schema::hasTable('tenant_daily_cash_handovers')) {
            Schema::create('tenant_daily_cash_handovers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('handover_no', 50)->unique();
                $table->foreignId('collector_id')->constrained('users')->onDelete('cascade');
                $table->date('handover_date');
                $table->string('shift_type', 30)->default('daily'); // morning, evening, night, full_day, daily
                $table->decimal('system_collected_amount', 12, 2)->default(0.00); // System calculated cash
                $table->decimal('handed_over_amount', 12, 2)->default(0.00); // Physical cash submitted
                $table->decimal('shortage_amount', 12, 2)->default(0.00); // Handed < System
                $table->decimal('excess_amount', 12, 2)->default(0.00); // Handed > System
                $table->decimal('digital_collected_amount', 12, 2)->default(0.00); // Informational digital (bKash/Nagad/Bank)
                $table->unsignedInteger('total_receipts_count')->default(0);
                $table->json('denominations')->nullable(); // Cash note count breakdown
                $table->string('status', 30)->default('pending'); // pending, approved, rejected
                $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->text('manager_remarks')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_daily_cash_handovers');
    }
};
