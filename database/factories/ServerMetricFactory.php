<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerMetricFactory extends Factory
{
    protected $model = ServerMetric::class;

    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'cpu_usage' => $this->faker->randomFloat(2, 5, 95),
            'memory_total' => 16384,
            'memory_used' => 8192,
            'memory_available' => 8192,
            'disk_total' => 320,
            'disk_used' => 120,
            'disk_usage' => 37.5,
            'load_1m' => $this->faker->randomFloat(2, 0.1, 4.0),
            'load_5m' => $this->faker->randomFloat(2, 0.1, 3.5),
            'load_15m' => $this->faker->randomFloat(2, 0.1, 3.0),
            'network_rx' => $this->faker->numberBetween(10000, 5000000),
            'network_tx' => $this->faker->numberBetween(10000, 5000000),
            'disk_read' => $this->faker->numberBetween(1000, 500000),
            'disk_write' => $this->faker->numberBetween(1000, 500000),
            'process_count' => $this->faker->numberBetween(80, 250),
            'open_file_descriptors' => $this->faker->numberBetween(500, 3000),
            'active_tcp_connections' => $this->faker->numberBetween(10, 500),
            'recorded_at' => now(),
        ];
    }
}
