<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('ssh_host_key_fingerprint', 100)->nullable()->after('encrypted_ssh_password');
            $table->string('trusted_ssh_host_key_fingerprint', 100)->nullable()->after('ssh_host_key_fingerprint');
            $table->string('ssh_host_key_policy', 30)->default('tofu')->after('trusted_ssh_host_key_fingerprint')->comment('strict, tofu (trust-on-first-use), unverified');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn([
                'ssh_host_key_fingerprint',
                'trusted_ssh_host_key_fingerprint',
                'ssh_host_key_policy',
            ]);
        });
    }
};
