<?php

namespace Database\Factories;

use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Models\Server;
use App\Models\ServerEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerEventFactory extends Factory
{
    protected $model = ServerEvent::class;

    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'event_type' => ServerEventType::SERVER_REGISTERED,
            'severity' => ServerEventSeverity::INFO,
            'message' => 'Server node registered into cluster.',
            'metadata' => ['initiator' => 'root_admin'],
            'occurred_at' => now(),
            'resolved_at' => null,
        ];
    }
}
