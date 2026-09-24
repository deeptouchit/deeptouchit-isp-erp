<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tenant Subscriptions Table
        if (!Schema::hasTable('tenant_subscriptions')) {
            Schema::create('tenant_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('saas_plans')->cascadeOnDelete();
                $table->string('billing_cycle')->default('monthly'); // monthly, quarterly, yearly, custom
                $table->timestamp('started_at')->nullable();
                $table->date('current_period_start')->nullable();
                $table->date('current_period_end')->nullable();
                $table->date('next_billing_date')->nullable();
                $table->timestamp('grace_period_ends_at')->nullable();
                $table->string('status')->default('active'); // trial, active, grace_period, past_due, suspended, expired, cancelled
                $table->boolean('auto_renew')->default(true);
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('suspended_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Enhance Saas Invoices Table for Comprehensive Financials
        Schema::table('saas_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('saas_invoices', 'tenant_subscription_id')) {
                $table->foreignId('tenant_subscription_id')->nullable()->after('tenant_id')->constrained('tenant_subscriptions')->nullOnDelete();
            }
            if (!Schema::hasColumn('saas_invoices', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0.00)->after('amount');
            }
            if (!Schema::hasColumn('saas_invoices', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0.00)->after('subtotal');
            }
            if (!Schema::hasColumn('saas_invoices', 'tax')) {
                $table->decimal('tax', 10, 2)->default(0.00)->after('discount');
            }
            if (!Schema::hasColumn('saas_invoices', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->default(0.00)->after('tax');
            }
            if (!Schema::hasColumn('saas_invoices', 'credit_amount')) {
                $table->decimal('credit_amount', 10, 2)->default(0.00)->after('paid_amount');
            }
            if (!Schema::hasColumn('saas_invoices', 'due_amount')) {
                $table->decimal('due_amount', 10, 2)->default(0.00)->after('credit_amount');
            }
            if (!Schema::hasColumn('saas_invoices', 'period_start')) {
                $table->date('period_start')->nullable()->after('due_amount');
            }
            if (!Schema::hasColumn('saas_invoices', 'period_end')) {
                $table->date('period_end')->nullable()->after('period_start');
            }
            if (!Schema::hasColumn('saas_invoices', 'notes')) {
                $table->text('notes')->nullable()->after('paid_at');
            }
        });

        // 3. Subscription Invoice Items Table
        if (!Schema::hasTable('subscription_invoice_items')) {
            Schema::create('subscription_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('saas_invoice_id')->constrained('saas_invoices')->cascadeOnDelete();
                $table->string('description');
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 10, 2)->default(0.00);
                $table->decimal('total_price', 10, 2)->default(0.00);
                $table->timestamps();
            });
        }

        // 4. Payment Transactions Table
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('saas_invoice_id')->nullable()->constrained('saas_invoices')->nullOnDelete();
                $table->string('payment_method')->default('online'); // bkash, nagad, rocket, bank, wallet, cash, online
                $table->string('gateway')->nullable(); // bkash, nagad, sslcommerz, shurjopay, manual, wallet
                $table->string('transaction_reference')->unique(); // Unique Trx ID / Payment Ref
                $table->decimal('amount', 10, 2);
                $table->string('currency', 10)->default('BDT');
                $table->string('status')->default('pending'); // pending, processing, successful, failed, cancelled
                $table->json('gateway_response')->nullable();
                $table->timestamp('initiated_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // 5. Tenant Wallets Table
        if (!Schema::hasTable('tenant_wallets')) {
            Schema::create('tenant_wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
                $table->decimal('balance', 12, 2)->default(0.00);
                $table->string('currency', 10)->default('BDT');
                $table->timestamps();
            });
        }

        // 6. Tenant Wallet Transactions Table
        if (!Schema::hasTable('tenant_wallet_transactions')) {
            Schema::create('tenant_wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('wallet_id')->constrained('tenant_wallets')->cascadeOnDelete();
                $table->enum('type', ['credit', 'debit']);
                $table->decimal('amount', 10, 2);
                $table->decimal('balance_after', 12, 2);
                $table->string('description');
                $table->string('reference')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_wallet_transactions');
        Schema::dropIfExists('tenant_wallets');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('subscription_invoice_items');
        
        Schema::table('saas_invoices', function (Blueprint $table) {
            $table->dropForeign(['tenant_subscription_id']);
            $table->dropColumn([
                'tenant_subscription_id',
                'subtotal',
                'discount',
                'tax',
                'paid_amount',
                'credit_amount',
                'due_amount',
                'period_start',
                'period_end',
                'notes'
            ]);
        });

        Schema::dropIfExists('tenant_subscriptions');
    }
};
