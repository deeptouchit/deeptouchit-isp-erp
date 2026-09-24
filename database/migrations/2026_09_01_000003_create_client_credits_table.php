<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'credit_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('credit_balance', 10, 2)->default(0.00)->after('status');
            });
        }

        Schema::create('client_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['add', 'deduct', 'auto_apply', 'refund'])->default('add');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2)->default(0.00);
            $table->decimal('balance_after', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('BDT');
            $table->string('description', 255);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type'], 'idx_credits_user_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_credits');
        if (Schema::hasColumn('users', 'credit_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('credit_balance');
            });
        }
    }
};
