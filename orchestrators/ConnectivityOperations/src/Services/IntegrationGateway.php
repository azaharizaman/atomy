<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Services;

use Nexus\ConnectivityOperations\Contracts\IntegrationGatewayCoordinatorInterface;
use Nexus\Connector\Contracts\HttpClientInterface;
use Nexus\Crypto\Contracts\KeyRotationServiceInterface;
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\Telemetry\Contracts\TelemetryTrackerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class IntegrationGateway
 *
 * A smart gateway for all external 3rd-party integrations.
 */
final readonly class IntegrationGateway implements IntegrationGatewayCoordinatorInterface
{
    public function __construct(
        private HttpClientInterface $connector,
        private KeyRotationServiceInterface $crypto,
        private TelemetryTrackerInterface $telemetry,
        private FeatureFlagManagerInterface $featureFlags,
        private LoggerInterface $logger
    ) {}

    /**
     * @inheritDoc
     */
    public function call(string $providerId, string $endpoint, array $payload = [], array $options = []): array
    {
        // 1. Check if the integration is enabled
        if (!$this->featureFlags->isEnabled("integration.{$providerId}")) {
            $this->logger->warning("Integration disabled via feature flag", ['provider' => $providerId]);
            throw new \RuntimeException("Integration [{$providerId}] is currently disabled.");
        }

        $startTime = microtime(true);
        $this->logger->info("Calling external provider: {$providerId}");

        try {
            // 2. Execute call via Connector (handles retries/circuit breaking)
            $response = $this->connector->request('POST', $endpoint, [
                'json' => $payload,
                'timeout' => $options['timeout'] ?? 30
            ]);

            $this->telemetry->trackMetric("integration.{$providerId}.latency", microtime(true) - $startTime);
            $this->telemetry->trackMetric("integration.{$providerId}.success", 1);

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->telemetry->trackMetric("integration.{$providerId}.failure", 1);
            $this->logger->error("External call failed", [
                'provider' => $providerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function rotateSecrets(string $providerId): bool
    {
        $this->logger->info("Rotating secrets for provider: {$providerId}");
        
        try {
            return $this->crypto->rotate("integration.{$providerId}.api_key");
        } catch (\Throwable $e) {
            $this->logger->error("Secret rotation failed", ['provider' => $providerId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function checkIntegrationsHealth(): array
    {
        // Logic to ping providers or check recent success rates in Telemetry
        return [
            'stripe' => 'healthy',
            'twilio' => 'degraded',
            'aws_s3' => 'healthy'
        ];
    }
}
