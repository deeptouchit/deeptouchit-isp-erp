<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_coverage_zones')) {
            Schema::create('tenant_coverage_zones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('reseller_id')->nullable()->constrained('tenant_resellers')->nullOnDelete();
                $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('city')->nullable();
                $table->string('area')->nullable();
                $table->integer('total_ports')->default(0);
                $table->integer('used_ports')->default(0);
                $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_coverage_zones');
    }
};
