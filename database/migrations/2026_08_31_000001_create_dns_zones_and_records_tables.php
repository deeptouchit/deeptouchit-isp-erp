<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('domain', 191)->unique();
            $table->string('primary_ns', 191)->default('ns1.deeptouchit.com');
            $table->string('secondary_ns', 191)->default('ns2.deeptouchit.com');
            $table->string('admin_email', 191)->default('hostmaster.deeptouchit.com');
            $table->string('serial', 30)->default('2026083101');
            $table->integer('refresh')->default(86400);
            $table->integer('retry')->default(7200);
            $table->integer('expire')->default(3600000);
            $table->integer('ttl')->default(86400);
            $table->string('status', 30)->default('active'); // active, disabled, pending
            $table->boolean('dnssec_enabled')->default(false);
            $table->string('zone_file_path', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dns_zone_id')->constrained('dns_zones')->cascadeOnDelete();
            $table->string('name', 191)->default('@'); // @, www, mail, etc.
            $table->string('type', 20)->default('A'); // A, AAAA, CNAME, MX, TXT, NS, SRV, CAA, PTR, SOA
            $table->text('content'); // IP, domain, TXT string
            $table->integer('ttl')->default(3600);
            $table->integer('priority')->nullable(); // For MX / SRV
            $table->integer('weight')->nullable();
            $table->integer('port')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->index(['dns_zone_id', 'type']);
            $table->index(['dns_zone_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_records');
        Schema::dropIfExists('dns_zones');
    }
};
