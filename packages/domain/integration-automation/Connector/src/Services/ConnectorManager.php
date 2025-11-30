<?php

declare(strict_types=1);

namespace Nexus\Connector\Services;

use Nexus\Connector\Contracts\{CircuitBreakerStorageInterface, CredentialProviderInterface, HttpClientInterface, IdempotencyStoreInterface, IntegrationLoggerInterface};
use Nexus\Connector\Exceptions\{CircuitBreakerOpenException, ConnectionException, RateLimitExceededException};
use Nexus\Connector\ValueObjects\{CircuitBreakerState, CircuitState, Endpoint, HttpMethod, IdempotencyKey, IntegrationLog, IntegrationStatus};
use Symfony\Component\Uid\Ulid;

/**
 * Central manager for external API connectivity with resilience patterns.
 *
 * This service orchestrates:
 * - Circuit breaker pattern
 * - Retry logic with exponential backoff
 * - Rate limiting with token bucket algorithm
 * - Idempotency key handling
 * - Integration logging
 * - Credential management
 * - OAuth token refresh
 *
 * STATELESS: All state is delegated to injected storage interfaces.
 * This ensures horizontal scalability across PHP-FPM workers and Laravel Octane.
 */
final class ConnectorManager
{
    public function __construct(
        private readonly CredentialProviderInterface $credentialProvider,
        private readonly IntegrationLoggerInterface $integrationLogger,
        private readonly RetryHandler $retryHandler,
        private readonly RateLimiter $rateLimiter,
        private readonly HttpClientInterface $httpClient,
        private readonly IdempotencyStoreInterface $idempotencyStore,
        private readonly CircuitBreakerStorageInterface $circuitBreakerStorage,
    ) {}

    /**
     * Execute an HTTP request with full resilience patterns.
     *
     * @param string $serviceName Name of the external service
     * @param Endpoint $endpoint Endpoint configuration
     * @param array<string, mixed> $payload Request payload
     * @param string|null $tenantId Optional tenant identifier
     * @param IdempotencyKey|null $idempotencyKey Optional idempotency key for duplicate prevention
     * @return array<string, mixed> Response data
     * @throws CircuitBreakerOpenException If circuit breaker is open
     * @throws RateLimitExceededException If rate limit is exceeded
     * @throws ConnectionException If all retry attempts fail
     */
    public function execute(
        string $serviceName,
        Endpoint $endpoint,
        array $payload = [],
        ?string $tenantId = null,
        ?IdempotencyKey $idempotencyKey = null
    ): array {
        // Check idempotency - return cached response if exists
        if ($idempotencyKey !== null) {
            $cachedResponse = $this->idempotencyStore->retrieve($idempotencyKey, $serviceName);
            if ($cachedResponse !== null) {
                return $cachedResponse;
            }
        }

        // Check circuit breaker
        $circuit = $this->getCircuitState($serviceName);
        
        if (!$circuit->allowsRequests()) {
            $secondsRemaining = $circuit->timeoutSeconds - 
                (time() - ($circuit->openedAt?->getTimestamp() ?? time()));
            
            throw CircuitBreakerOpenException::open($serviceName, max(0, $secondsRemaining));
        }

        // Check rate limit
        if ($endpoint->rateLimitConfig !== null) {
            $this->rateLimiter->checkAndConsume($serviceName, $endpoint->rateLimitConfig);
        }

        // Get credentials and refresh if expired
        $credentials = $this->credentialProvider->getCredentials($serviceName, $tenantId);
        
        if ($credentials->isExpired() && $credentials->refreshToken !== null) {
            $credentials = $this->credentialProvider->refreshCredentials($serviceName, $tenantId);
        }

        // Execute with retry logic
        $startTime = hrtime(true);
        $logId = $this->generateLogId();

        try {
            $response = $this->retryHandler->execute(
                callback: fn(int $attempt) => $this->httpClient->send(
                    $endpoint,
                    $payload,
                    $credentials->data
                ),
                retryPolicy: $endpoint->retryPolicy
            );

            $duration = (int) ((hrtime(true) - $startTime) / 1_000_000);
            $responseBody = $response['body'] ?? [];

            // Store idempotency result
            if ($idempotencyKey !== null) {
                $this->idempotencyStore->store($idempotencyKey, $responseBody, $serviceName);
            }

            // Log success
            $this->integrationLogger->log(
                IntegrationLog::success(
                    id: $logId,
                    serviceName: $serviceName,
                    endpoint: $endpoint->url,
                    method: $endpoint->method,
                    httpStatusCode: $response['status_code'] ?? 200,
                    durationMs: $duration,
                    requestData: $this->sanitizeData($payload),
                    responseData: $this->sanitizeData($responseBody),
                    tenantId: $tenantId
                )
            );

            // Record success in circuit breaker
            $this->updateCircuitState($serviceName, $circuit->recordSuccess());

            return $responseBody;

        } catch (\Throwable $e) {
            $duration = (int) ((hrtime(true) - $startTime) / 1_000_000);

            // Log failure
            $this->integrationLogger->log(
                IntegrationLog::failed(
                    id: $logId,
                    serviceName: $serviceName,
                    endpoint: $endpoint->url,
                    method: $endpoint->method,
                    errorMessage: $e->getMessage(),
                    httpStatusCode: $e instanceof ConnectionException ? $e->httpStatusCode : null,
                    durationMs: $duration,
                    requestData: $this->sanitizeData($payload),
                    tenantId: $tenantId
                )
            );

            // Record failure in circuit breaker
            $this->updateCircuitState($serviceName, $circuit->recordFailure());

            throw $e;
        }
    }

    /**
     * Get circuit breaker state for a service.
     */
    private function getCircuitState(string $serviceName): CircuitBreakerState
    {
        if (!$this->circuitBreakerStorage->hasState($serviceName)) {
            $initialState = CircuitBreakerState::closed();
            $this->circuitBreakerStorage->setState($serviceName, $initialState);
            return $initialState;
        }

        $circuit = $this->circuitBreakerStorage->getState($serviceName);

        // Transition to half-open if timeout has passed
        if ($circuit->state === CircuitState::OPEN && $circuit->shouldAttemptReset()) {
            $circuit = $circuit->halfOpen();
            $this->circuitBreakerStorage->setState($serviceName, $circuit);
        }

        return $circuit;
    }

    /**
     * Update circuit breaker state for a service.
     */
    private function updateCircuitState(string $serviceName, CircuitBreakerState $state): void
    {
        $this->circuitBreakerStorage->setState($serviceName, $state);
    }

    /**
     * Sanitize sensitive data before logging.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeData(array $data): array
    {
        $sensitive = ['password', 'token', 'api_key', 'secret', 'authorization', 'credit_card'];
        
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            
            foreach ($sensitive as $pattern) {
                if (str_contains($lowerKey, $pattern)) {
                    $data[$key] = '***REDACTED***';
                    break;
                }
            }
            
            if (is_array($value)) {
                $data[$key] = $this->sanitizeData($value);
            }
        }
        
        return $data;
    }

    /**
     * Generate unique log ID (ULID).
     */
    private function generateLogId(): string
    {
        return (string) new Ulid();
    }
}
