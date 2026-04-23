<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Coordinators;

use DateTimeImmutable;
use DateTimeZone;
use Nexus\IntelligenceOperations\Contracts\AiCapabilityCatalogInterface;
use Nexus\IntelligenceOperations\Contracts\AiStatusCoordinatorInterface;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityDefinition;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiEndpointHealthSnapshot;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\IntelligenceOperations\Exceptions\AiStatusContractException;

final readonly class AiStatusCoordinator implements AiStatusCoordinatorInterface
{
    public function __construct(
        private AiCapabilityCatalogInterface $capabilityCatalog,
    ) {
    }

    /**
     * @param array<int, AiEndpointHealthSnapshot> $endpointGroupHealthSnapshots
     */
    public function snapshot(
        string $mode,
        array $endpointGroupHealthSnapshots,
        ?DateTimeImmutable $generatedAt = null,
    ): AiStatusSnapshot {
        AiStatusSchema::assertMode($mode);
        $generatedAt ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $capabilityDefinitions = $this->capabilityCatalog->all();
        $this->assertUniqueCapabilityDefinitions($capabilityDefinitions);
        $endpointSnapshots = $this->indexEndpointSnapshots($endpointGroupHealthSnapshots);
        $capabilityStatuses = [];

        foreach ($capabilityDefinitions as $definition) {
            $capabilityStatuses[$definition->featureKey] = $this->buildCapabilityStatus(
                $mode,
                $definition,
                $endpointSnapshots,
            );
        }

        return new AiStatusSnapshot(
            mode: $mode,
            globalHealth: $this->resolveGlobalHealth($mode, $capabilityStatuses),
            capabilityDefinitions: $capabilityDefinitions,
            capabilityStatuses: $capabilityStatuses,
            endpointGroupHealthSnapshots: array_values($endpointSnapshots),
            reasonCodes: $this->buildSnapshotReasonCodes($mode, $capabilityStatuses),
            generatedAt: $generatedAt,
        );
    }

    /**
     * @param array<int, AiEndpointHealthSnapshot> $endpointGroupHealthSnapshots
     * @return array<string, AiEndpointHealthSnapshot>
     */
    private function indexEndpointSnapshots(array $endpointGroupHealthSnapshots): array
    {
        $indexed = [];

        foreach ($endpointGroupHealthSnapshots as $snapshot) {
            if (!$snapshot instanceof AiEndpointHealthSnapshot) {
                throw AiStatusContractException::invalidValue('AI endpoint group health snapshots');
            }

            if (isset($indexed[$snapshot->endpointGroup])) {
                throw AiStatusContractException::invalidValue('AI endpoint group health snapshots');
            }

            $indexed[$snapshot->endpointGroup] = $snapshot;
        }

        ksort($indexed);

        return $indexed;
    }

    /**
     * @param array<int, AiCapabilityDefinition> $capabilityDefinitions
     */
    private function assertUniqueCapabilityDefinitions(array $capabilityDefinitions): void
    {
        $seen = [];

        foreach ($capabilityDefinitions as $definition) {
            if (!$definition instanceof AiCapabilityDefinition) {
                throw AiStatusContractException::invalidValue('AI capability definitions');
            }

            $featureKey = trim($definition->featureKey);
            if ($featureKey === '') {
                throw AiStatusContractException::invalidValue('AI capability definitions');
            }

            if (isset($seen[$featureKey])) {
                throw AiStatusContractException::invalidValue('AI capability definitions');
            }

            $seen[$featureKey] = true;
        }
    }

    /**
     * @param array<string, AiEndpointHealthSnapshot> $endpointSnapshots
     */
    private function buildCapabilityStatus(
        string $mode,
        AiCapabilityDefinition $definition,
        array $endpointSnapshots,
    ): AiCapabilityStatus {
        $endpointSnapshot = $endpointSnapshots[$definition->endpointGroup] ?? null;

        if (!$definition->requiresAi) {
            return $this->buildStatus(
                definition: $definition,
                status: AiCapabilityStatus::STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['ai_not_required'],
                endpointSnapshot: $endpointSnapshot,
                mode: $mode,
            );
        }

        if ($mode === AiStatusSchema::MODE_OFF) {
            return $this->buildStatus(
                definition: $definition,
                status: AiCapabilityStatus::STATUS_DISABLED,
                available: false,
                reasonCodes: ['ai_disabled_by_config'],
                endpointSnapshot: null,
                mode: $mode,
            );
        }

        if ($mode === AiStatusSchema::MODE_DETERMINISTIC) {
            return $this->buildStatus(
                definition: $definition,
                status: $definition->hasManualFallback ? AiCapabilityStatus::STATUS_DEGRADED : AiCapabilityStatus::STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: $definition->hasManualFallback
                    ? ['deterministic_fallback_mode', 'manual_fallback_available']
                    : ['deterministic_fallback_mode', 'provider_unavailable'],
                endpointSnapshot: null,
                mode: $mode,
            );
        }

        if ($endpointSnapshot === null) {
            return $this->buildStatus(
                definition: $definition,
                status: $definition->hasManualFallback ? AiCapabilityStatus::STATUS_DEGRADED : AiCapabilityStatus::STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: $definition->hasManualFallback
                    ? ['endpoint_not_configured', 'manual_fallback_available']
                    : ['endpoint_not_configured'],
                endpointSnapshot: null,
                mode: $mode,
            );
        }

        return match ($endpointSnapshot->health) {
            AiStatusSchema::HEALTH_HEALTHY => $this->buildStatus(
                definition: $definition,
                status: AiCapabilityStatus::STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['provider_available', 'endpoint_group_healthy'],
                endpointSnapshot: $endpointSnapshot,
                mode: $mode,
            ),
            AiStatusSchema::HEALTH_DEGRADED => $this->buildStatus(
                definition: $definition,
                status: $definition->hasManualFallback ? AiCapabilityStatus::STATUS_DEGRADED : AiCapabilityStatus::STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: $definition->hasManualFallback
                    ? $this->mergeReasonCodes($endpointSnapshot->reasonCodes, ['provider_degraded', 'endpoint_group_degraded', 'manual_fallback_available'])
                    : $this->mergeReasonCodes($endpointSnapshot->reasonCodes, ['provider_degraded', 'endpoint_group_degraded']),
                endpointSnapshot: $endpointSnapshot,
                mode: $mode,
            ),
            AiStatusSchema::HEALTH_UNAVAILABLE => $this->buildStatus(
                definition: $definition,
                status: $definition->hasManualFallback ? AiCapabilityStatus::STATUS_DEGRADED : AiCapabilityStatus::STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: $definition->hasManualFallback
                    ? $this->mergeReasonCodes($endpointSnapshot->reasonCodes, ['provider_unavailable', 'endpoint_group_unavailable', 'manual_fallback_available'])
                    : $this->mergeReasonCodes($endpointSnapshot->reasonCodes, ['provider_unavailable', 'endpoint_group_unavailable']),
                endpointSnapshot: $endpointSnapshot,
                mode: $mode,
            ),
            AiStatusSchema::HEALTH_DISABLED => $this->buildStatus(
                definition: $definition,
                status: AiCapabilityStatus::STATUS_DISABLED,
                available: false,
                reasonCodes: ['provider_disabled', 'endpoint_group_disabled', 'endpoint_disabled_by_config'],
                endpointSnapshot: null,
                mode: $mode,
            ),
            default => throw AiStatusContractException::invalidValue('AI endpoint health'),
        };
    }

    /**
     * @param list<string> $reasonCodes
     */
    private function buildStatus(
        AiCapabilityDefinition $definition,
        string $status,
        bool $available,
        array $reasonCodes,
        ?AiEndpointHealthSnapshot $endpointSnapshot,
        string $mode,
    ): AiCapabilityStatus {
        return new AiCapabilityStatus(
            featureKey: $definition->featureKey,
            capabilityGroup: $definition->capabilityGroup,
            endpointGroup: $definition->endpointGroup,
            fallbackUiMode: $definition->fallbackUiMode,
            messageKey: $definition->degradationMessageKey,
            status: $status,
            available: $available,
            reasonCodes: $this->normalizeReasonCodes($reasonCodes),
            operatorCritical: $definition->operatorCritical,
            diagnostics: $this->buildDiagnostics($definition, $endpointSnapshot, $mode, $status),
        );
    }

    /**
     * @param list<string> $reasonCodes
     * @return list<string>
     */
    private function normalizeReasonCodes(array $reasonCodes): array
    {
        return AiStatusSchema::sanitizeReasonCodes($reasonCodes);
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     * @return list<string>
     */
    private function mergeReasonCodes(array $left, array $right): array
    {
        return $this->normalizeReasonCodes(array_merge($left, $right));
    }

    /**
     * @param array<string, AiCapabilityStatus> $capabilityStatuses
     */
    private function resolveGlobalHealth(
        string $mode,
        array $capabilityStatuses,
    ): string {
        if ($mode === AiStatusSchema::MODE_OFF) {
            return AiStatusSchema::HEALTH_DISABLED;
        }

        if ($mode === AiStatusSchema::MODE_DETERMINISTIC) {
            return AiStatusSchema::HEALTH_DEGRADED;
        }

        if ($capabilityStatuses === []) {
            return AiStatusSchema::HEALTH_UNAVAILABLE;
        }

        foreach ($capabilityStatuses as $status) {
            if ($status->status !== AiCapabilityStatus::STATUS_AVAILABLE) {
                return AiStatusSchema::HEALTH_DEGRADED;
            }
        }

        return AiStatusSchema::HEALTH_HEALTHY;
    }

    /**
     * @param array<string, AiCapabilityStatus> $capabilityStatuses
     * @return list<string>
     */
    private function buildSnapshotReasonCodes(
        string $mode,
        array $capabilityStatuses,
    ): array {
        $reasonCodes = [];

        foreach ($capabilityStatuses as $status) {
            $reasonCodes = array_merge($reasonCodes, $status->reasonCodes);
        }

        if ($mode === AiStatusSchema::MODE_OFF) {
            $reasonCodes[] = 'ai_disabled_by_config';
        } elseif ($mode === AiStatusSchema::MODE_DETERMINISTIC) {
            $reasonCodes[] = 'deterministic_fallback_mode';
        }

        return $this->normalizeReasonCodes($reasonCodes);
    }

    private function buildDiagnostics(
        AiCapabilityDefinition $definition,
        ?AiEndpointHealthSnapshot $endpointSnapshot,
        string $mode,
        string $status,
    ): array {
        return AiStatusSchema::sanitizeDiagnostics([
            'mode' => $mode,
            'status' => $status,
            'capability_group' => $definition->capabilityGroup,
            'endpoint_group' => $definition->endpointGroup,
            'endpoint_health' => $endpointSnapshot?->health,
            'endpoint_latency_ms' => $endpointSnapshot?->latencyMs,
            'requires_ai' => $definition->requiresAi,
            'manual_fallback_available' => $definition->hasManualFallback,
        ]);
    }
}
