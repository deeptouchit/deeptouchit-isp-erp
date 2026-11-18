<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_customer_invoices')) {
            Schema::table('tenant_customer_invoices', function (Blueprint $table): void {
                if (!Schema::hasColumn('tenant_customer_invoices', 'reseller_id')) {
                    $table->foreignId('reseller_id')->nullable()->after('tenant_id')->constrained('tenant_resellers')->nullOnDelete();
                }
                if (!Schema::hasColumn('tenant_customer_invoices', 'package_id')) {
                    $table->foreignId('package_id')->nullable()->after('customer_id')->constrained('tenant_internet_packages')->nullOnDelete();
                }
                if (!Schema::hasColumn('tenant_customer_invoices', 'vat_tax')) {
                    $table->decimal('vat_tax', 12, 2)->default(0)->after('amount');
                }
                if (!Schema::hasColumn('tenant_customer_invoices', 'total_payable')) {
                    $table->decimal('total_payable', 12, 2)->default(0)->after('discount');
                }
                if (!Schema::hasColumn('tenant_customer_invoices', 'issue_date')) {
                    $table->date('issue_date')->nullable()->after('due_amount');
                }
                if (!Schema::hasColumn('tenant_customer_invoices', 'payment_method')) {
                    $table->string('payment_method', 40)->nullable()->after('paid_at');
                }
                if (!Schema::hasColumn('tenant_customer_invoices', 'auto_generated')) {
                    $table->boolean('auto_generated')->default(false)->after('payment_method');
                }
                if (!Schema::hasColumn('tenant_customer_invoices', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('auto_generated')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('tenant_customer_payments')) {
            Schema::table('tenant_customer_payments', function (Blueprint $table): void {
                if (!Schema::hasColumn('tenant_customer_payments', 'invoice_no')) {
                    $table->string('invoice_no')->nullable()->after('invoice_id');
                }
                if (!Schema::hasColumn('tenant_customer_payments', 'billing_month')) {
                    $table->string('billing_month', 20)->nullable()->after('invoice_no');
                }
                if (!Schema::hasColumn('tenant_customer_payments', 'transaction_id')) {
                    $table->string('transaction_id', 120)->nullable()->after('payment_method');
                }
                if (!Schema::hasColumn('tenant_customer_payments', 'request_key')) {
                    $table->string('request_key', 100)->nullable()->after('transaction_id');
                    $table->unique(['tenant_id', 'request_key'], 'customer_payments_tenant_request_unique');
                }
                if (!Schema::hasColumn('tenant_customer_payments', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable()->after('status');
                }
                if (!Schema::hasColumn('tenant_customer_payments', 'sms_sent')) {
                    $table->boolean('sms_sent')->default(false)->after('paid_at');
                }
                if (!Schema::hasColumn('tenant_customer_payments', 'sms_sent_at')) {
                    $table->timestamp('sms_sent_at')->nullable()->after('sms_sent');
                }
            });
        }

        if (!Schema::hasTable('tenant_customer_payment_allocations')) {
            Schema::create('tenant_customer_payment_allocations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('payment_id')->constrained('tenant_customer_payments')->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained('tenant_customer_invoices')->cascadeOnDelete();
                $table->decimal('amount', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->timestamps();

                $table->unique(['payment_id', 'invoice_id'], 'customer_payment_invoice_unique');
                $table->index(['tenant_id', 'invoice_id'], 'customer_allocations_tenant_invoice_index');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: this compatibility migration may run against
        // installations where some canonical columns already existed before deployment.
    }
};
