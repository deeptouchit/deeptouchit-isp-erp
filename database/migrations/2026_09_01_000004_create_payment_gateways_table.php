<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->enum('category', ['mfs', 'card', 'bank', 'international'])->default('mfs');
            $table->json('credentials')->nullable();
            $table->enum('mode', ['sandbox', 'live'])->default('sandbox');
            $table->enum('fee_type', ['percentage', 'fixed', 'none'])->default('none');
            $table->decimal('fee_value', 10, 2)->default(0.00);
            $table->decimal('min_amount', 10, 2)->default(10.00);
            $table->decimal('max_amount', 10, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('icon', 100)->nullable();
            $table->text('instructions')->nullable();
            $table->timestamps();

            $table->index(['slug', 'is_active'], 'idx_gateways_slug_active');
        });

        // Seed Default Authoritative Gateways
        $defaultGateways = [
            [
                'slug' => 'bkash',
                'name' => 'bKash Merchant PGW',
                'category' => 'mfs',
                'credentials' => json_encode([
                    'app_key' => 'bk_app_key_demo',
                    'app_secret' => 'bk_app_secret_demo',
                    'username' => 'deeptouchhost_merchant',
                    'password' => '••••••••••••',
                ]),
                'mode' => 'live',
                'fee_type' => 'percentage',
                'fee_value' => 1.50,
                'min_amount' => 10.00,
                'max_amount' => 50000.00,
                'sort_order' => 1,
                'is_active' => true,
                'instructions' => 'Instant automated clearance via bKash Checkout URL API.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'nagad',
                'name' => 'Nagad Direct PGW',
                'category' => 'mfs',
                'credentials' => json_encode([
                    'merchant_id' => 'NAGAD_68001',
                    'merchant_private_key' => '••••••••••••••••••••',
                    'pgw_public_key' => '••••••••••••••••••••',
                ]),
                'mode' => 'live',
                'fee_type' => 'percentage',
                'fee_value' => 1.45,
                'min_amount' => 10.00,
                'max_amount' => 50000.00,
                'sort_order' => 2,
                'is_active' => true,
                'instructions' => 'Instant clearance via Nagad Direct Merchant API.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'sslcommerz',
                'name' => 'SSLCommerz Multi-Card',
                'category' => 'card',
                'credentials' => json_encode([
                    'store_id' => 'deeptouchhostlive',
                    'store_password' => '••••••••••••',
                ]),
                'mode' => 'live',
                'fee_type' => 'percentage',
                'fee_value' => 2.50,
                'min_amount' => 50.00,
                'max_amount' => 250000.00,
                'sort_order' => 3,
                'is_active' => true,
                'instructions' => 'Supports Visa, MasterCard, UnionPay, Amex and all Bangladeshi debit/credit cards.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'stripe',
                'name' => 'Stripe Global Payments',
                'category' => 'international',
                'credentials' => json_encode([
                    'publishable_key' => 'pk_live_51O9••••••••••••••••',
                    'secret_key' => 'sk_live_51O9••••••••••••••••',
                    'webhook_secret' => 'whsec_••••••••••••••••',
                ]),
                'mode' => 'live',
                'fee_type' => 'percentage',
                'fee_value' => 2.90,
                'min_amount' => 100.00,
                'max_amount' => 500000.00,
                'sort_order' => 4,
                'is_active' => true,
                'instructions' => 'Accept international credit cards and Apple Pay in USD / BDT.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'paypal',
                'name' => 'PayPal Express Checkout',
                'category' => 'international',
                'credentials' => json_encode([
                    'client_id' => 'PAYPAL_CLIENT_ID_LIVE',
                    'client_secret' => '••••••••••••••••••••',
                ]),
                'mode' => 'sandbox',
                'fee_type' => 'percentage',
                'fee_value' => 3.50,
                'min_amount' => 100.00,
                'max_amount' => 500000.00,
                'sort_order' => 5,
                'is_active' => false,
                'instructions' => 'International PayPal account deposits and subscriptions.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'bank',
                'name' => 'Bank Wire & Manual Deposit',
                'category' => 'bank',
                'credentials' => json_encode([
                    'bank_name' => 'City Bank Ltd.',
                    'account_name' => 'DeepTouchHost Technologies BD',
                    'account_number' => '1102983746001',
                    'routing_number' => '225271890',
                    'branch_name' => 'Gulshan Branch, Dhaka',
                ]),
                'mode' => 'live',
                'fee_type' => 'none',
                'fee_value' => 0.00,
                'min_amount' => 500.00,
                'max_amount' => 1000000.00,
                'sort_order' => 6,
                'is_active' => true,
                'instructions' => 'Deposit directly to company bank account and submit deposit slip / cheque number.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('payment_gateways')->insert($defaultGateways);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
