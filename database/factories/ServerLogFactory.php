<?php

namespace Database\Factories;

use App\Enums\Infrastructure\ServerLogLevel;
use App\Enums\Infrastructure\ServerLogType;
use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerLogFactory extends Factory
{
    protected $model = ServerLog::class;

    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'log_type' => ServerLogType::SYSTEM,
            'level' => ServerLogLevel::INFO,
            'message' => 'System metrics sampled successfully.',
            'context' => ['source' => 'system_poller'],
            'occurred_at' => now(),
        ];
    }
}
