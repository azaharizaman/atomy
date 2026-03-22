<?php

declare(strict_types=1);

namespace App\Http\Idempotency;

use App\Exceptions\IdempotencyEnvelopeTooLargeException;
use Illuminate\Http\JsonResponse;
use JsonException;
use Nexus\Idempotency\ValueObjects\ResultEnvelope;

final class IdempotencyReplayEnvelope
{
    /**
     * @throws IdempotencyEnvelopeTooLargeException
     * @throws JsonException
     */
    public static function encode(JsonResponse $response): string
    {
        $payload = [
            'version' => 1,
            'status' => $response->getStatusCode(),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => $response->getContent(),
        ];
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (strlen($json) > ResultEnvelope::MAX_BYTES) {
            throw new IdempotencyEnvelopeTooLargeException('Response too large to store for replay');
        }

        return $json;
    }
}
