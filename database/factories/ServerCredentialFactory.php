<?php

namespace Database\Factories;

use App\Enums\Infrastructure\ServerCredentialStatus;
use App\Enums\Infrastructure\ServerCredentialType;
use App\Models\Server;
use App\Models\ServerCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerCredentialFactory extends Factory
{
    protected $model = ServerCredential::class;

    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'credential_type' => ServerCredentialType::SSH_PASSWORD,
            'name' => 'Root Access Password',
            'username' => 'root',
            'encrypted_secret' => 'SuperSecretRootPass123!',
            'fingerprint' => 'SHA256:d8a9fbc' . $this->faker->regexify('[a-f0-9]{20}'),
            'expires_at' => now()->addMonths(6),
            'last_rotated_at' => now(),
            'status' => ServerCredentialStatus::ACTIVE,
        ];
    }
}
