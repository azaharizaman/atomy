<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Workflows;

use Nexus\ConnectivityOperations\Contracts\ConnectivityTelemetryPortInterface;
use Nexus\ConnectivityOperations\Contracts\ProviderCallPortInterface;
use Nexus\ConnectivityOperations\Contracts\ProviderHealthStoreInterface;
use Nexus\ConnectivityOperations\DTOs\ProviderCallRequest;
use Nexus\ConnectivityOperations\Rules\ProviderCallRule;
use Nexus\ConnectivityOperations\Rules\ProviderFeatureFlagRule;

final readonly class ProviderCallWorkflow
{
    public function __construct(
        private ProviderCallRule $providerCallRule,
        private ProviderFeatureFlagRule $providerFeatureFlagRule,
        private ProviderCallPortInterface $providerCallPort,
        private ConnectivityTelemetryPortInterface $telemetryPort,
        private ProviderHealthStoreInterface $healthStore,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function run(ProviderCallRequest $request): array
    {
        $this->providerCallRule->assert($request);
        $this->providerFeatureFlagRule->assertEnabled($request->providerId, $request->options['context'] ?? []);

        $start = microtime(true);

        try {
            $response = $this->providerCallPort->call(
                $request->providerId,
                $request->endpoint,
                $request->payload,
                $request->options
            );

            $durationMs = (microtime(true) - $start) * 1000;
            try {
                $this->healthStore->record($request->providerId, [
                    'status' => 'healthy',
                    'latency_ms' => round($durationMs, 3),
                    'last_checked_at' => gmdate(DATE_ATOM),
                ]);
            } catch (\Throwable) {
                // Health-store persistence is best-effort; do not break successful calls.
            }
            $this->telemetryPort->increment('connectivity.provider.success', 1.0, ['provider' => $request->providerId]);
            $this->telemetryPort->timing('connectivity.provider.latency_ms', $durationMs, ['provider' => $request->providerId]);

            return $response;
        } catch (\Throwable $e) {
            $durationMs = (microtime(true) - $start) * 1000;
            $this->telemetryPort->increment('connectivity.provider.failure', 1.0, ['provider' => $request->providerId]);
            $this->telemetryPort->timing('connectivity.provider.latency_ms', $durationMs, ['provider' => $request->providerId]);
            try {
                $this->healthStore->record($request->providerId, [
                    'status' => 'degraded',
                    'error' => $e->getMessage(),
                    'latency_ms' => round($durationMs, 3),
                    'last_checked_at' => gmdate(DATE_ATOM),
                ]);
            } catch (\Throwable) {
                // Keep original provider-call exception semantics.
            }

            throw $e;
        }
    }
}
