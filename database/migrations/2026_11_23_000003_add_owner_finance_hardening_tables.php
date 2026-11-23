<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'owner_capabilities')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->json('owner_capabilities')->nullable()->after('role');
            });
        }

        if (! Schema::hasTable('request_idempotency_keys')) {
            Schema::create('request_idempotency_keys', function (Blueprint $table): void {
                $table->id();
                $table->string('scope', 100);
                $table->string('idempotency_key', 255);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('request_hash', 64);
                $table->unsignedSmallInteger('response_status')->nullable();
                $table->json('response_body')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->unique(['scope', 'idempotency_key']);
                $table->index(['user_id']);
            });
        }

        if (! Schema::hasTable('financial_ledger_entries')) {
            Schema::create('financial_ledger_entries', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('saas_invoice_id')->nullable();
                $table->unsignedBigInteger('payment_transaction_id')->nullable();
                $table->string('entry_type', 40);
                $table->decimal('amount', 19, 4);
                $table->string('currency', 10);
                $table->string('reference', 255);
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'created_at']);
                $table->index(['saas_invoice_id', 'created_at']);
                $table->unique(['entry_type', 'reference']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_ledger_entries');
        Schema::dropIfExists('request_idempotency_keys');
        if (Schema::hasColumn('users', 'owner_capabilities')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('owner_capabilities'));
        }
    }
};
