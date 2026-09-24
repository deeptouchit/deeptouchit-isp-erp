<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firewall_rules', function (Blueprint $table) {
            $table->id();
            $table->string('label', 191);
            $table->string('port', 100);
            $table->string('protocol', 20)->default('tcp'); // tcp, udp, any
            $table->string('action', 20)->default('allow'); // allow, deny, reject, limit
            $table->string('direction', 10)->default('in'); // in, out
            $table->string('from_ip', 100)->default('Anywhere');
            $table->string('to_ip', 100)->nullable();
            $table->boolean('is_system')->default(false);
            $table->string('status', 30)->default('active');
            $table->integer('ufw_rule_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firewall_rules');
    }
};
