<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

use Nexus\IntelligenceOperations\Contracts\AiStatusCoordinatorInterface;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiEndpointHealthSnapshot;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\MachineLearning\Contracts\AiHealthProbeInterface;
use Nexus\MachineLearning\ValueObjects\AiEndpointHealthSnapshot as RuntimeEndpointHealthSnapshot;

use App\Adapters\Ai\Contracts\AiEndpointRegistryInterface;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;

final readonly class AiRuntimeStatusAdapter implements AiRuntimeStatusInterface
{
    public function __construct(
        private AiEndpointRegistryInterface $endpointRegistry,
        private AiHealthProbeInterface $healthProbe,
        private AiStatusCoordinatorInterface $statusCoordinator,
    ) {
    }

    public function snapshot(): AiStatusSnapshot
    {
        $checkedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $mode = AiStatusSchema::MODE_PROVIDER;

        try {
            $mode = $this->endpointRegistry->mode();
            $endpointSnapshots = [];

            foreach ($this->endpointRegistry->endpointGroups() as $endpointGroup) {
                if ($mode === AiStatusSchema::MODE_OFF) {
                    $endpointSnapshots[] = $this->buildStaticEndpointSnapshot(
                        endpointGroup: $endpointGroup,
                        mode: $mode,
                        health: AiStatusSchema::HEALTH_DISABLED,
                        checkedAt: $checkedAt,
                        reasonCodes: ['ai_disabled_by_config', 'endpoint_disabled_by_config'],
                    );

                    continue;
                }

                if ($mode === AiStatusSchema::MODE_DETERMINISTIC) {
                    $endpointSnapshots[] = $this->buildStaticEndpointSnapshot(
                        endpointGroup: $endpointGroup,
                        mode: $mode,
                        health: AiStatusSchema::HEALTH_DISABLED,
                        checkedAt: $checkedAt,
                        reasonCodes: ['deterministic_fallback_mode', 'endpoint_disabled_by_config'],
                    );

                    continue;
                }

                $endpointConfig = $this->endpointRegistry->endpointConfig($endpointGroup);

                if ($endpointConfig === null) {
                    $endpointSnapshots[] = $this->buildStaticEndpointSnapshot(
                        endpointGroup: $endpointGroup,
                        mode: $mode,
                        health: AiStatusSchema::HEALTH_UNAVAILABLE,
                        checkedAt: $checkedAt,
                        reasonCodes: ['endpoint_not_configured'],
                    );

                    continue;
                }

                $endpointSnapshots[] = $this->mapRuntimeSnapshot($this->healthProbe->probe($endpointConfig));
            }

            return $this->statusCoordinator->snapshot($mode, $endpointSnapshots, $checkedAt);
        } catch (Throwable) {
            return $this->buildFallbackSnapshot($mode, $checkedAt);
        }
    }

    public function capabilityStatus(string $featureKey): ?AiCapabilityStatus
    {
        return $this->snapshot()->capabilityStatuses[$featureKey] ?? null;
    }

    public function providerName(): string
    {
        try {
            return $this->endpointRegistry->providerName();
        } catch (Throwable) {
            return 'openrouter';
        }
    }

    /**
     * @param list<string> $reasonCodes
     */
    private function buildStaticEndpointSnapshot(
        string $endpointGroup,
        string $mode,
        string $health,
        DateTimeImmutable $checkedAt,
        array $reasonCodes,
    ): AiEndpointHealthSnapshot {
        return new AiEndpointHealthSnapshot(
            endpointGroup: $endpointGroup,
            health: $health,
            checkedAt: $checkedAt,
            reasonCodes: $reasonCodes,
            diagnostics: [
                'mode' => $mode,
                'endpoint_group' => $endpointGroup,
                'provider_name' => $this->providerName(),
            ],
        );
    }

    private function buildFallbackSnapshot(string $mode, DateTimeImmutable $checkedAt): AiStatusSnapshot
    {
        $endpointSnapshots = [];
        $health = match ($mode) {
            AiStatusSchema::MODE_OFF,
            AiStatusSchema::MODE_DETERMINISTIC => AiStatusSchema::HEALTH_DISABLED,
            default => AiStatusSchema::HEALTH_UNAVAILABLE,
        };
        $reasonCodes = match ($mode) {
            AiStatusSchema::MODE_OFF => ['ai_disabled_by_config', 'endpoint_disabled_by_config'],
            AiStatusSchema::MODE_DETERMINISTIC => ['deterministic_fallback_mode', 'endpoint_disabled_by_config'],
            default => ['provider_unavailable', 'endpoint_group_unavailable'],
        };

        foreach (AiStatusSchema::endpointGroups() as $endpointGroup) {
            $endpointSnapshots[] = $this->buildStaticEndpointSnapshot(
                endpointGroup: $endpointGroup,
                mode: $mode,
                health: $health,
                checkedAt: $checkedAt,
                reasonCodes: $reasonCodes,
            );
        }

        return $this->statusCoordinator->snapshot(
            $mode,
            $endpointSnapshots,
            $checkedAt,
        );
    }

    private function mapRuntimeSnapshot(RuntimeEndpointHealthSnapshot $snapshot): AiEndpointHealthSnapshot
    {
        return new AiEndpointHealthSnapshot(
            endpointGroup: $snapshot->endpointGroup->value,
            health: $snapshot->health->value,
            checkedAt: $snapshot->checkedAt,
            reasonCodes: $snapshot->reasonCodes,
            latencyMs: $snapshot->latencyMs,
            diagnostics: $snapshot->diagnostics,
        );
    }
}
