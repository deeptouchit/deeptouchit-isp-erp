<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('company_name');
                $table->string('slug')->unique();
                $table->string('domain')->nullable()->unique();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->string('btrc_license_no')->nullable();
                $table->string('btrc_license_type')->nullable()->default('Nationwide ISP');
                $table->string('trade_license_no')->nullable();
                $table->string('tin_bin_no')->nullable();
                $table->string('support_hotline')->nullable();
                $table->string('billing_phone')->nullable();
                $table->string('billing_email')->nullable();
                $table->string('mail_driver')->default('smtp');
                $table->string('mail_host')->nullable();
                $table->integer('mail_port')->default(587);
                $table->string('mail_username')->nullable();
                $table->text('mail_password')->nullable();
                $table->string('mail_encryption')->default('tls');
                $table->string('mail_from_address')->nullable();
                $table->string('mail_from_name')->nullable();
                $table->boolean('mail_enabled')->default(false);
                $table->string('website')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('contact_person_designation')->nullable();
                $table->string('currency_code', 10)->default('BDT');
                $table->string('currency_symbol', 10)->default('৳');
                $table->string('timezone', 50)->default('Asia/Dhaka');
                $table->text('invoice_footer_notes')->nullable();
                $table->string('logo')->nullable();
                $table->string('favicon')->nullable();
                $table->foreignId('saas_plan_id')->nullable()->constrained('saas_plans')->nullOnDelete();
                $table->enum('status', ['active', 'suspended', 'pending', 'cancelled'])->default('active');
                $table->date('subscription_expires_at')->nullable();
                $table->decimal('wallet_balance', 12, 2)->default(0.00);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
