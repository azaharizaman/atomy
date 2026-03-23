<?php

declare(strict_types=1);

namespace App\Http\Idempotency;

use App\Exceptions\IdempotencyEnvelopeTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\Idempotency\ValueObjects\ResultEnvelope;
use Nexus\Laravel\Idempotency\Http\IdempotencyRequest;

final class IdempotencyCompletion
{
    /**
     * @throws IdempotencyEnvelopeTooLargeException
     * @throws JsonException
     */
    public static function succeed(
        Request $request,
        IdempotencyServiceInterface $idempotency,
        JsonResponse $response,
    ): JsonResponse {
        $ctx = $request->attributes->get('idempotency_request');
        if (! $ctx instanceof IdempotencyRequest) {
            return $response;
        }

        $payload = IdempotencyReplayEnvelope::encode($response);
        $idempotency->complete(
            $ctx->tenantId,
            $ctx->operationRef,
            $ctx->clientKey,
            $ctx->fingerprint,
            $ctx->attemptToken,
            new ResultEnvelope($payload),
        );

        return $response;
    }

    public static function fail(Request $request, IdempotencyServiceInterface $idempotency): void
    {
        $ctx = $request->attributes->get('idempotency_request');
        if (! $ctx instanceof IdempotencyRequest) {
            return;
        }

        $idempotency->fail(
            $ctx->tenantId,
            $ctx->operationRef,
            $ctx->clientKey,
            $ctx->fingerprint,
            $ctx->attemptToken,
        );
    }
}
