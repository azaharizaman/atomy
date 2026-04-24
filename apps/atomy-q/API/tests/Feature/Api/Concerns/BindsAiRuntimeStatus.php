<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Concerns;

use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;

trait BindsAiRuntimeStatus
{
    /**
     * @param array<string, AiCapabilityStatus> $capabilityStatuses
     */
    protected function bindAiRuntimeStatus(array $capabilityStatuses): void
    {
        $snapshot = new AiStatusSnapshot(
            mode: AiStatusSchema::MODE_PROVIDER,
            globalHealth: AiStatusSchema::HEALTH_HEALTHY,
            capabilityDefinitions: [],
            capabilityStatuses: $capabilityStatuses,
            endpointGroupHealthSnapshots: [],
            reasonCodes: [],
            generatedAt: new \DateTimeImmutable('2026-04-24T03:00:00Z'),
        );

        $this->app->instance(AiRuntimeStatusInterface::class, new readonly class($snapshot) implements AiRuntimeStatusInterface {
            public function __construct(private AiStatusSnapshot $snapshot)
            {
            }

            public function snapshot(): AiStatusSnapshot
            {
                return $this->snapshot;
            }

            public function capabilityStatus(string $featureKey): ?AiCapabilityStatus
            {
                return $this->snapshot->capabilityStatuses[$featureKey] ?? null;
            }

            public function providerName(): string
            {
                return 'openrouter';
            }
        });
    }
}
