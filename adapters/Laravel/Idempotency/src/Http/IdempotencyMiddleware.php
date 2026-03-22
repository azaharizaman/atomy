<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Http;

use Closure;
use Illuminate\Http\Request;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\Idempotency\Enums\BeginOutcome;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\TenantId;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    public function __construct(
        private readonly IdempotencyServiceInterface $idempotencyService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $headerName = config('nexus-idempotency.middleware.header_name', 'Idempotency-Key');
        $tenantHeader = config('nexus-idempotency.middleware.tenant_header', 'X-Tenant-ID');
        
        $clientKeyValue = $request->header($headerName);
        
        if (empty($clientKeyValue)) {
            return response()->json([
                'error' => 'Idempotency-Key header required',
            ], 400);
        }

        $tenantIdValue = $request->header($tenantHeader);
        
        if (empty($tenantIdValue)) {
            $user = $request->user();
            $tenantIdValue = $user?->tenant_id;
        }

        if (empty($tenantIdValue)) {
            return response()->json([
                'error' => 'Tenant identification failed',
            ], 400);
        }

        $tenantId = new TenantId($tenantIdValue);
        $operationRef = new OperationRef($request->method() . ' ' . $request->path());
        $clientKey = new ClientKey($clientKeyValue);
        $fingerprint = $this->computeFingerprint($request);

        $decision = $this->idempotencyService->begin(
            $tenantId,
            $operationRef,
            $clientKey,
            $fingerprint
        );

        if ($decision->outcome === BeginOutcome::InProgress) {
            return response()->json([
                'error' => 'Duplicate request in progress',
            ], 409)->withHeaders([
                'Retry-After' => 60,
            ]);
        }

        if ($decision->outcome === BeginOutcome::Replay && $decision->resultEnvelope !== null) {
            return response()->json(
                $decision->resultEnvelope->value,
                200
            );
        }

        $request->attributes->set('idempotency_request', new IdempotencyRequest(
            $tenantId,
            $operationRef,
            $clientKey,
            $fingerprint,
            $decision->record->attemptToken
        ));

        return $next($request);
    }

    private function computeFingerprint(Request $request): RequestFingerprint
    {
        $data = [
            'method' => $request->method(),
            'uri' => $request->getPathInfo(),
            'query' => $request->query->all(),
            'body' => $request->except(['password', 'token', 'secret']),
        ];

        return new RequestFingerprint(json_encode($data));
    }
}
