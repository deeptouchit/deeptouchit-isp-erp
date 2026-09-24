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
        Schema::create('tenant_internet_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable();
            $table->string('service_type', 50)->default('pppoe'); // pppoe
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('router_id')->nullable();
            $table->unsignedBigInteger('ip_pool_id')->nullable();

            // MikroTik pppoe Profile Policies
            $table->string('name', 150)->nullable(); //  মাইক্রোটিকে 
            $table->string('local_address', 100)->nullable(); //  মাইক্রোটিকে 
            $table->string('remote_address', 100)->nullable(); //  মাইক্রোটিকে 
            $table->string('only_one', 20)->default('default');  // default, yes, no
            $table->text('comment')->nullable(); //  মাইক্রোটিকে 
            
            // Pricing & Billing ডাটাবেইস 
            $table->string('package_name', 150)->nullable();
            $table->decimal('price', 10, 2)->default(500.00);
            $table->string('validity_days')->default(30);
            $table->string('validity_unit', 20)->default('days'); // days, hours, months
            $table->string('upload_speed')->default(0);
            $table->string('download_speed')->default(0);
            $table->string('facebook_speed')->default(0);
            $table->string('youtube_speed')->default(0);
            $table->string('bdix_speed')->default(0);

  
            // MikroTik & RADIUS Mapping
            $table->string('mikrotik_profile', 150)->nullable();
            $table->string('address_list', 100)->nullable();
            $table->boolean('is_sync_mikrotik')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('subscribers_count')->default(0);
            
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'service_type']);
            $table->index(['tenant_id', 'router_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_internet_packages');
    }
};
