<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->foreignId('server_group_id')->nullable()->after('uuid')->constrained('server_groups')->onDelete('set null');
            $table->string('server_type', 30)->default('worker')->after('server_group_id')->index();
            $table->string('environment', 30)->default('production')->after('server_type')->index();
            $table->string('health_status', 30)->default('healthy')->after('status')->index();
            $table->boolean('is_master')->default(false)->after('health_status')->index();
            
            $table->string('primary_ip', 45)->nullable()->after('ip_address');
            $table->string('ipv6', 45)->nullable()->after('primary_ip');
            
            $table->string('os_name', 100)->nullable()->after('os');
            $table->string('os_version', 50)->nullable()->after('os_name');
            $table->string('kernel_version', 100)->nullable()->after('os_version');
            $table->string('architecture', 30)->default('x86_64')->after('kernel_version');
            
            $table->unsignedSmallInteger('ssh_port')->default(22)->after('architecture');
            $table->string('ssh_user', 50)->default('root')->after('ssh_port');
            $table->string('auth_type', 30)->default('password')->after('ssh_user');
            $table->longText('encrypted_ssh_key')->nullable()->after('auth_type');
            $table->text('encrypted_ssh_password')->nullable()->after('encrypted_ssh_key');
            
            $table->string('agent_token', 255)->nullable()->after('encrypted_ssh_password')->index();
            $table->string('agent_version', 50)->nullable()->after('agent_token');
            $table->timestamp('agent_installed_at')->nullable()->after('agent_version');
            
            $table->timestamp('last_seen_at')->nullable()->after('last_ping_at');
            $table->timestamp('last_health_check_at')->nullable()->after('last_seen_at');
            $table->timestamp('maintenance_at')->nullable()->after('last_health_check_at');
            $table->string('maintenance_reason', 255)->nullable()->after('maintenance_at');
            $table->timestamp('decommissioned_at')->nullable()->after('maintenance_reason');
            
            $table->softDeletes()->after('updated_at');
            
            $table->index('status');
            $table->index('ip_address');
            $table->index('hostname');
        });

        // Backfill UUID and master flag for existing server records
        $existingServers = DB::table('servers')->get();
        foreach ($existingServers as $server) {
            DB::table('servers')->where('id', $server->id)->update([
                'uuid' => (string) Str::uuid(),
                'primary_ip' => $server->ip_address,
                'is_master' => ($server->id == 1),
                'server_type' => ($server->id == 1 ? 'master' : 'worker'),
                'os_name' => 'Ubuntu',
                'os_version' => '24.04',
                'last_seen_at' => $server->last_ping_at ?? now(),
                'last_health_check_at' => $server->last_ping_at ?? now(),
            ]);
        }

        // Add unique index on uuid after backfill
        Schema::table('servers', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['server_group_id']);
            $table->dropUnique(['uuid']);
            $table->dropIndex(['status']);
            $table->dropIndex(['ip_address']);
            $table->dropIndex(['hostname']);
            
            $table->dropColumn([
                'uuid',
                'server_group_id',
                'server_type',
                'environment',
                'health_status',
                'is_master',
                'primary_ip',
                'ipv6',
                'os_name',
                'os_version',
                'kernel_version',
                'architecture',
                'ssh_port',
                'ssh_user',
                'auth_type',
                'encrypted_ssh_key',
                'encrypted_ssh_password',
                'agent_token',
                'agent_version',
                'agent_installed_at',
                'last_seen_at',
                'last_health_check_at',
                'maintenance_at',
                'maintenance_reason',
                'decommissioned_at',
                'deleted_at'
            ]);
        });
    }
};
