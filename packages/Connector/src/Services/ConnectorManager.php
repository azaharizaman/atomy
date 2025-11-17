<?php

declare(strict_types=1);

namespace Nexus\Connector\Services;

use Nexus\Connector\Contracts\{CredentialProviderInterface, IntegrationLoggerInterface};
use Nexus\Connector\Exceptions\{CircuitBreakerOpenException, ConnectionException};
use Nexus\Connector\ValueObjects\{CircuitBreakerState, CircuitState, Endpoint, HttpMethod, IntegrationLog, IntegrationStatus};
use Symfony\Component\Uid\Ulid;

/**
 * Central manager for external API connectivity with resilience patterns.
 *
 * This service orchestrates:
 * - Circuit breaker pattern
 * - Retry logic with exponential backoff
 * - Integration logging
 * - Credential management
 */
final class ConnectorManager
{
    /** @var array<string, CircuitBreakerState> Circuit breaker states per service */
    private array $circuitStates = [];

    public function __construct(
        private readonly CredentialProviderInterface $credentialProvider,
        private readonly IntegrationLoggerInterface $integrationLogger,
        private readonly RetryHandler $retryHandler,
    ) {}

    /**
     * Execute an HTTP request with full resilience patterns.
     *
     * @param string $serviceName Name of the external service
     * @param Endpoint $endpoint Endpoint configuration
     * @param array<string, mixed> $payload Request payload
     * @param string|null $tenantId Optional tenant identifier
     * @return array<string, mixed> Response data
     * @throws CircuitBreakerOpenException If circuit breaker is open
     * @throws ConnectionException If all retry attempts fail
     */
    public function execute(
        string $serviceName,
        Endpoint $endpoint,
        array $payload = [],
        ?string $tenantId = null
    ): array {
        // Check circuit breaker
        $circuit = $this->getCircuitState($serviceName);
        
        if (!$circuit->allowsRequests()) {
            $secondsRemaining = $circuit->timeoutSeconds - 
                (time() - ($circuit->openedAt?->getTimestamp() ?? time()));
            
            throw CircuitBreakerOpenException::open($serviceName, max(0, $secondsRemaining));
        }

        // Get credentials
        $credentials = $this->credentialProvider->getCredentials($serviceName, $tenantId);

        // Execute with retry logic
        $startTime = hrtime(true);
        $logId = $this->generateLogId();

        try {
            $response = $this->retryHandler->execute(
                callback: fn(int $attempt) => $this->sendRequest(
                    $endpoint,
                    $payload,
                    $credentials->data,
                    $attempt
                ),
                retryPolicy: $endpoint->retryPolicy
            );

            $duration = (int) ((hrtime(true) - $startTime) / 1_000_000);

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
                    responseData: $this->sanitizeData($response['body'] ?? []),
                    tenantId: $tenantId
                )
            );

            // Record success in circuit breaker
            $this->updateCircuitState($serviceName, $circuit->recordSuccess());

            return $response['body'] ?? [];

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
     * Send HTTP request (to be implemented by application layer or using HTTP client).
     * This is a placeholder - actual implementation should use Guzzle, cURL, or similar.
     *
     * @param Endpoint $endpoint
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $credentials
     * @param int $attempt
     * @return array{status_code: int, body: array<string, mixed>}
     */
    private function sendRequest(
        Endpoint $endpoint,
        array $payload,
        array $credentials,
        int $attempt
    ): array {
        // This method should be overridden or delegated to an HTTP client implementation
        // For now, it's a placeholder that throws an exception
        throw new \LogicException(
            'sendRequest must be implemented by providing an HttpClientInterface implementation'
        );
    }

    /**
     * Get circuit breaker state for a service.
     */
    private function getCircuitState(string $serviceName): CircuitBreakerState
    {
        if (!isset($this->circuitStates[$serviceName])) {
            $this->circuitStates[$serviceName] = CircuitBreakerState::closed();
        }

        $circuit = $this->circuitStates[$serviceName];

        // Transition to half-open if timeout has passed
        if ($circuit->state === CircuitState::OPEN && $circuit->shouldAttemptReset()) {
            $circuit = $circuit->halfOpen();
            $this->circuitStates[$serviceName] = $circuit;
        }

        return $circuit;
    }

    /**
     * Update circuit breaker state for a service.
     */
    private function updateCircuitState(string $serviceName, CircuitBreakerState $state): void
    {
        $this->circuitStates[$serviceName] = $state;
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
