<?php

namespace Database\Factories;

use App\Enums\Infrastructure\ServerServiceStatus;
use App\Models\Server;
use App\Models\ServerService;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerServiceFactory extends Factory
{
    protected $model = ServerService::class;

    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'service_name' => 'nginx',
            'display_name' => 'Nginx Web Server',
            'service_type' => 'web_server',
            'status' => ServerServiceStatus::RUNNING,
            'version' => '1.24.0',
            'port' => 80,
            'enabled' => true,
            'pid' => $this->faker->numberBetween(1000, 9999),
            'cpu_usage' => 1.2,
            'memory_usage' => 64,
            'last_checked_at' => now(),
            'metadata' => ['workers' => 4],
        ];
    }
}
