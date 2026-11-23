<?php

namespace App\Services\Security;

use App\Models\RequestIdempotencyKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyService
{
    public function begin(Request $request, string $scope): ?RequestIdempotencyKey
    {
        $key = trim((string) $request->header('Idempotency-Key'));
        if ($key === '' || strlen($key) > 255) {
            return null;
        }

        $hash = hash('sha256', $request->method().'|'.$request->path().'|'.$request->getContent());
        $record = DB::transaction(function () use ($request, $scope, $key, $hash): RequestIdempotencyKey {
            $record = RequestIdempotencyKey::query()->where('scope', $scope)->where('idempotency_key', $key)->lockForUpdate()->first();
            if ($record) {
                abort_if($record->request_hash !== $hash, Response::HTTP_CONFLICT, 'Idempotency key was reused with a different request.');
                abort_if($record->completed_at === null, Response::HTTP_CONFLICT, 'An identical request is already being processed.');
                return $record;
            }

            return RequestIdempotencyKey::create([
                'scope' => $scope,
                'idempotency_key' => $key,
                'user_id' => $request->user()?->id,
                'request_hash' => $hash,
            ]);
        });

        return $record->completed_at ? $record : null;
    }

    public function complete(Request $request, string $scope, array $body, int $status = 200): void
    {
        $key = trim((string) $request->header('Idempotency-Key'));
        if ($key === '') {
            return;
        }

        RequestIdempotencyKey::query()
            ->where('scope', $scope)
            ->where('idempotency_key', $key)
            ->update(['response_status' => $status, 'response_body' => $body, 'completed_at' => now()]);
    }
}
