<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Http;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\Idempotency\Enums\BeginOutcome;
use Nexus\Idempotency\Exceptions\IdempotencyFingerprintConflictException;
use Nexus\Idempotency\Exceptions\IdempotencyKeyInvalidException;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\TenantId;
use Nexus\Laravel\Idempotency\Contracts\ReplayResponseFactoryInterface;
use Nexus\Laravel\Idempotency\Support\RequestFingerprintFactory;
use Symfony\Component\HttpFoundation\Response;

final readonly class IdempotencyMiddleware
{
    public function __construct(
        private IdempotencyServiceInterface $idempotencyService,
        private RequestFingerprintFactory $fingerprintFactory,
        private ReplayResponseFactoryInterface $replayResponseFactory,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $routeName = $route?->getName();
        if ($routeName === null || $routeName === '') {
            return $this->jsonError(
                'Idempotency operation reference missing',
                'idempotency_operation_ref_missing',
                500,
            );
        }

        $headerKey = $request->header('Idempotency-Key');
        if ($headerKey === null || trim($headerKey) === '') {
            return $this->jsonError(
                'Idempotency-Key header is required',
                'idempotency_key_required',
                400,
            );
        }

        try {
            $clientKey = new ClientKey(trim($headerKey));
        } catch (IdempotencyKeyInvalidException $e) {
            return $this->jsonError(
                'Invalid Idempotency-Key',
                'idempotency_key_invalid',
                400,
            );
        }

        $tenantRaw = $request->attributes->get('auth_tenant_id');
        if (! is_string($tenantRaw) || $tenantRaw === '') {
            return $this->jsonError(
                'Internal error',
                'idempotency_tenant_missing',
                500,
            );
        }

        $tenantId = new TenantId($tenantRaw);
        $operationRef = new OperationRef($routeName);
        $fingerprint = $this->fingerprintFactory->make($request);

        try {
            $decision = $this->idempotencyService->begin(
                $tenantId,
                $operationRef,
                $clientKey,
                $fingerprint,
            );
        } catch (IdempotencyFingerprintConflictException) {
            return $this->jsonError(
                'Idempotency conflict',
                'idempotency_fingerprint_conflict',
                409,
            );
        }

        if ($decision->outcome === BeginOutcome::Replay) {
            $replay = $decision->replayResult;
            if ($replay === null) {
                return $this->jsonError(
                    'Internal error',
                    'idempotency_replay_missing',
                    500,
                );
            }

            return $this->replayResponseFactory->fromPayloadString($replay->payload);
        }

        if ($decision->outcome === BeginOutcome::InProgress) {
            return $this->jsonError(
                'Request is already in progress',
                'idempotency_in_progress',
                409,
            );
        }

        $record = $decision->record;
        if ($record === null) {
            return $this->jsonError(
                'Internal error',
                'idempotency_record_missing',
                500,
            );
        }

        $request->attributes->set(
            'idempotency_request',
            new IdempotencyRequest(
                $tenantId,
                $operationRef,
                $clientKey,
                $fingerprint,
                $record->attemptToken,
            ),
        );

        return $next($request);
    }

    private function jsonError(string $message, string $code, int $status): JsonResponse
    {
        return new JsonResponse([
            'error' => $message,
            'code' => $code,
        ], $status);
    }
}
