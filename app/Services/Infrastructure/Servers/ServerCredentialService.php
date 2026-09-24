<?php

namespace App\Services\Infrastructure\Servers;

use App\Enums\Infrastructure\ServerCredentialStatus;
use App\Enums\Infrastructure\ServerCredentialType;
use App\Exceptions\Infrastructure\Servers\ServerCredentialException;
use App\Models\Server;
use App\Models\ServerCredential;
use Illuminate\Support\Facades\DB;

class ServerCredentialService
{
    /**
     * Store a new encrypted credential for a server.
     */
    public function storeCredential(
        Server $server,
        ServerCredentialType $type,
        string $name,
        string $username,
        string $secret,
        ?string $fingerprint = null
    ): ServerCredential {
        if (empty(trim($secret))) {
            throw new ServerCredentialException("Cannot store an empty secret for credential '{$name}'.");
        }

        return DB::transaction(function () use ($server, $type, $name, $username, $secret, $fingerprint) {
            // If storing a primary SSH credential, archive older active credentials of the same type
            ServerCredential::where('server_id', $server->id)
                ->where('credential_type', $type)
                ->where('status', ServerCredentialStatus::ACTIVE)
                ->update(['status' => ServerCredentialStatus::EXPIRED]);

            return ServerCredential::create([
                'server_id' => $server->id,
                'credential_type' => $type,
                'name' => $name,
                'username' => $username,
                'encrypted_secret' => $secret, // Encrypted automatically via Eloquent cast
                'fingerprint' => $fingerprint,
                'status' => ServerCredentialStatus::ACTIVE,
            ]);
        });
    }

    /**
     * Retrieve the active credential for a server strictly in-memory.
     */
    public function getActiveCredential(Server $server, ServerCredentialType $type): ?ServerCredential
    {
        return ServerCredential::where('server_id', $server->id)
            ->where('credential_type', $type)
            ->where('status', ServerCredentialStatus::ACTIVE)
            ->latest()
            ->first();
    }

    /**
     * Rotate an existing credential with a new secret.
     */
    public function rotateCredential(ServerCredential $credential, string $newSecret): ServerCredential
    {
        if ($credential->status === ServerCredentialStatus::REVOKED) {
            throw new ServerCredentialException("Cannot rotate a revoked credential #{$credential->id}.");
        }

        $credential->update([
            'encrypted_secret' => $newSecret,
            'last_rotated_at' => now(),
            'status' => ServerCredentialStatus::ACTIVE,
        ]);

        return $credential;
    }

    /**
     * Revoke a credential.
     */
    public function revokeCredential(ServerCredential $credential): void
    {
        $credential->update([
            'status' => ServerCredentialStatus::REVOKED,
        ]);
    }
}
