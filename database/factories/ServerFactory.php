<?php

namespace Database\Factories;

use App\Enums\Infrastructure\ServerAuthType;
use App\Enums\Infrastructure\ServerEnvironment;
use App\Enums\Infrastructure\ServerHealthStatus;
use App\Enums\Infrastructure\ServerStatus;
use App\Enums\Infrastructure\ServerType;
use App\Models\Server;
use App\Models\ServerGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServerFactory extends Factory
{
    protected $model = Server::class;

    public function definition(): array
    {
        $ip = $this->faker->ipv4();
        return [
            'uuid' => (string) Str::uuid(),
            'server_group_id' => ServerGroup::factory(),
            'name' => 'Node-' . $this->faker->city(),
            'hostname' => $this->faker->domainName(),
            'ip_address' => $ip,
            'primary_ip' => $ip,
            'ipv6' => $this->faker->ipv6(),
            'server_type' => ServerType::WORKER,
            'environment' => ServerEnvironment::PRODUCTION,
            'health_status' => ServerHealthStatus::HEALTHY,
            'status' => ServerStatus::ACTIVE,
            'is_master' => false,
            'os' => 'Ubuntu 24.04',
            'os_name' => 'Ubuntu',
            'os_version' => '24.04',
            'kernel_version' => '6.8.0-31-generic',
            'architecture' => 'x86_64',
            'cpu_cores' => $this->faker->randomElement([4, 8, 16, 32]),
            'total_ram' => $this->faker->randomElement([8192, 16384, 32768, 65536]),
            'total_disk' => $this->faker->randomElement([160, 320, 640, 1280]),
            'used_ram' => 4096,
            'used_disk' => 45,
            'load_avg_1min' => 0.45,
            'load_avg_5min' => 0.38,
            'load_avg_15min' => 0.29,
            'ssh_port' => 22,
            'ssh_user' => 'root',
            'auth_type' => ServerAuthType::PASSWORD,
            'encrypted_ssh_password' => 'TestPassword123!',
            'last_ping_at' => now(),
            'last_seen_at' => now(),
            'last_health_check_at' => now(),
        ];
    }

    public function master(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_master' => true,
            'server_type' => ServerType::MASTER,
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServerStatus::OFFLINE,
            'health_status' => ServerHealthStatus::OFFLINE,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServerStatus::MAINTENANCE,
            'maintenance_at' => now(),
            'maintenance_reason' => 'Scheduled kernel patch',
        ]);
    }
}
