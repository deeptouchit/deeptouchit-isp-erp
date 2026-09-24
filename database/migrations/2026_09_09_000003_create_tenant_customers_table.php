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
        Schema::create('tenant_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('reseller_id')->nullable()->constrained('tenant_resellers')->onDelete('set null');
            
            // Identification
            $table->string('customer_id', 50)->index(); // CUST-1001
            $table->string('name', 191);
            $table->string('username', 100)->index(); // PPPoE Username / Login ID
            $table->string('password', 191)->nullable(); // PPPoE Password
            $table->string('phone', 50)->index();
            $table->string('alt_phone', 50)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('national_id', 50)->nullable(); // NID / Passport
            $table->string('father_name', 191)->nullable();
            $table->text('address')->nullable();
            $table->string('zone', 100)->nullable()->index(); // Zone / Area
            $table->string('division', 50)->nullable();
            $table->string('district', 50)->nullable();
            $table->string('thana', 50)->nullable();
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();

            // Technical Provisioning
            $table->string('connection_type', 30)->default('pppoe'); // pppoe, static_ip, hotspot, dhcp
            $table->foreignId('package_id')->nullable()->constrained('tenant_internet_packages')->onDelete('set null');
            $table->string('package_name', 100)->nullable();
            $table->decimal('monthly_bill', 10, 2)->default(0.00);
            $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->onDelete('set null');
            $table->string('ip_address', 45)->nullable();
            $table->string('mac_address', 50)->nullable();
            $table->foreignId('olt_id')->nullable()->constrained('tenant_olts')->onDelete('set null');
            $table->string('onu_mac_sn', 100)->nullable();
            $table->string('fiber_route_info', 191)->nullable(); // Splitter / Core color

            // Billing & Status
            $table->string('billing_type', 20)->default('prepaid'); // prepaid, postpaid
            $table->date('billing_cycle_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->string('status', 30)->default('active')->index(); // active, expired, due, suspended, disabled, archived
            $table->decimal('due_amount', 10, 2)->default(0.00);
            $table->decimal('wallet_balance', 10, 2)->default(0.00);
            $table->boolean('auto_cut_enabled')->default(true);
            $table->integer('grace_period_days')->default(2);
            $table->string('online_status', 20)->default('offline'); // online, offline
            $table->timestamp('last_online_at')->nullable();
            $table->foreignId('assigned_collector_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'customer_id'], 'tenant_customer_id_unique');
            $table->unique(['tenant_id', 'username'], 'tenant_customer_username_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_customers');
    }
};
