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
        Schema::create('tenant_nas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
            $table->string('nasname'); // IP or FQDN of the NAS client
            $table->string('shortname'); // Human recognizable identifier
            $table->enum('type', ['mikrotik', 'cisco', 'juniper', 'huawei', 'accel-ppp', 'other'])->default('mikrotik');
            $table->integer('ports')->default(1812);
            $table->text('secret'); // Encrypted with Laravel Crypt
            $table->string('server')->nullable(); // Virtual server
            $table->string('community')->nullable(); // SNMP Community
            $table->integer('coa_port')->default(3799); // RFC 3576 / 5176 CoA/PoD port
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            $table->timestamp('last_auth_at')->nullable();
            $table->timestamp('last_accounting_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'nasname']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_nas');
    }
};
