<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('payment_gateways')) {
            Schema::create('payment_gateways', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 50)->unique();
                $table->string('name', 100);
                $table->string('category', 50)->default('mfs');
                $table->json('credentials')->nullable();
                $table->string('mode', 20)->default('sandbox');
                $table->string('fee_type', 20)->default('none');
                $table->decimal('fee_value', 10, 2)->default(0.00);
                $table->decimal('min_amount', 10, 2)->default(10.00);
                $table->decimal('max_amount', 10, 2)->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->string('icon', 100)->nullable();
                $table->text('instructions')->nullable();
                $table->timestamps();

                $table->index(['slug', 'is_active']);
            });

            // Seed default gateways
            $defaultGateways = [
                [
                    'slug' => 'bkash',
                    'name' => 'bKash PGW',
                    'category' => 'mfs',
                    'mode' => 'sandbox',
                    'fee_type' => 'percentage',
                    'fee_value' => 1.50,
                    'min_amount' => 10.00,
                    'max_amount' => 50000.00,
                    'sort_order' => 1,
                    'is_active' => true,
                    'icon' => 'fas fa-mobile-alt',
                    'instructions' => 'bKash Merchant Payment Gateway',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'slug' => 'nagad',
                    'name' => 'Nagad Direct',
                    'category' => 'mfs',
                    'mode' => 'sandbox',
                    'fee_type' => 'percentage',
                    'fee_value' => 1.50,
                    'min_amount' => 10.00,
                    'max_amount' => 50000.00,
                    'sort_order' => 2,
                    'is_active' => true,
                    'icon' => 'fas fa-wallet',
                    'instructions' => 'Nagad Merchant Payment Gateway',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'slug' => 'rocket',
                    'name' => 'Rocket (DBBL)',
                    'category' => 'mfs',
                    'mode' => 'sandbox',
                    'fee_type' => 'percentage',
                    'fee_value' => 1.50,
                    'min_amount' => 10.00,
                    'max_amount' => 50000.00,
                    'sort_order' => 3,
                    'is_active' => true,
                    'icon' => 'fas fa-money-check',
                    'instructions' => 'DBBL Rocket Payment Gateway',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'slug' => 'sslcommerz',
                    'name' => 'SSLCommerz',
                    'category' => 'card',
                    'mode' => 'sandbox',
                    'fee_type' => 'percentage',
                    'fee_value' => 2.50,
                    'min_amount' => 10.00,
                    'max_amount' => 100000.00,
                    'sort_order' => 4,
                    'is_active' => true,
                    'icon' => 'fas fa-credit-card',
                    'instructions' => 'SSLCommerz Multi-Channel Payment Gateway',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'slug' => 'bank_qr',
                    'name' => 'Bank & Bangla QR',
                    'category' => 'bank',
                    'mode' => 'live',
                    'fee_type' => 'none',
                    'fee_value' => 0.00,
                    'min_amount' => 10.00,
                    'max_amount' => 500000.00,
                    'sort_order' => 5,
                    'is_active' => true,
                    'icon' => 'fas fa-qrcode',
                    'instructions' => 'Direct Bank Deposit and Bangla QR Transfers',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            DB::table('payment_gateways')->insert($defaultGateways);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
