<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\ValueObjects;

use DateTimeImmutable;
use Nexus\MachineLearning\Enums\AiHealth;
use Nexus\MachineLearning\Enums\AiMode;
use Nexus\MachineLearning\Exceptions\AiRuntimeContractException;

final readonly class AiRuntimeSnapshot
{
    /** @var array<int, AiCapabilityDefinition> */
    public array $capabilityDefinitions;

    /**
     * @var array<string, AiCapabilityStatus>
     */
    public array $capabilityStatuses;

    /** @var array<int, AiEndpointHealthSnapshot> */
    public array $endpointGroupHealthSnapshots;

    /** @var list<string> */
    public array $reasonCodes;

    /**
     * @param array<int, AiCapabilityDefinition> $capabilityDefinitions
     * @param array<string, AiCapabilityStatus> $capabilityStatuses
     * @param array<int, AiEndpointHealthSnapshot> $endpointGroupHealthSnapshots
     * @param list<string> $reasonCodes
     */
    public function __construct(
        public readonly AiMode $mode,
        public readonly AiHealth $globalHealth,
        array $capabilityDefinitions,
        array $capabilityStatuses,
        array $endpointGroupHealthSnapshots,
        array $reasonCodes,
        public readonly DateTimeImmutable $generatedAt,
    ) {
        $this->capabilityDefinitions = $this->assertCapabilityDefinitions($capabilityDefinitions);
        $this->capabilityStatuses = $this->normalizeCapabilityStatuses($capabilityStatuses);
        $this->endpointGroupHealthSnapshots = $this->assertEndpointHealthSnapshots($endpointGroupHealthSnapshots);
        $this->reasonCodes = $this->assertReasonCodes($reasonCodes);
    }

    public function toArray(): array
    {
        return [
            'mode' => $this->mode->value,
            'global_health' => $this->globalHealth->value,
            'reason_codes' => $this->reasonCodes,
            'generated_at' => $this->generatedAt->format(DATE_ATOM),
            'capability_definitions' => array_map(
                static fn (AiCapabilityDefinition $definition): array => $definition->toArray(),
                $this->capabilityDefinitions,
            ),
            'capability_statuses' => array_map(
                static fn (AiCapabilityStatus $status): array => $status->toArray(),
                $this->capabilityStatuses,
            ),
            'endpoint_groups' => array_map(
                static fn (AiEndpointHealthSnapshot $snapshot): array => $snapshot->toArray(),
                $this->endpointGroupHealthSnapshots,
            ),
        ];
    }

    /**
     * @param array<int, AiCapabilityDefinition> $capabilityDefinitions
     * @return array<int, AiCapabilityDefinition>
     */
    private function assertCapabilityDefinitions(array $capabilityDefinitions): array
    {
        foreach ($capabilityDefinitions as $definition) {
            if (!$definition instanceof AiCapabilityDefinition) {
                throw AiRuntimeContractException::invalidValue('AI capability definitions');
            }
        }

        usort(
            $capabilityDefinitions,
            static fn (AiCapabilityDefinition $left, AiCapabilityDefinition $right): int => $left->featureKey <=> $right->featureKey
        );

        return $capabilityDefinitions;
    }

    /**
     * @param array<string, AiCapabilityStatus> $capabilityStatuses
     * @return array<string, AiCapabilityStatus>
     */
    private function normalizeCapabilityStatuses(array $capabilityStatuses): array
    {
        $normalized = [];

        foreach ($capabilityStatuses as $featureKey => $status) {
            if (!is_string($featureKey) || trim($featureKey) === '') {
                throw AiRuntimeContractException::invalidValue('AI capability status keys');
            }

            if (!$status instanceof AiCapabilityStatus) {
                throw AiRuntimeContractException::invalidValue('AI capability status entries');
            }

            $normalized[$featureKey] = $status;
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param array<int, AiEndpointHealthSnapshot> $endpointGroupHealthSnapshots
     * @return array<int, AiEndpointHealthSnapshot>
     */
    private function assertEndpointHealthSnapshots(array $endpointGroupHealthSnapshots): array
    {
        foreach ($endpointGroupHealthSnapshots as $snapshot) {
            if (!$snapshot instanceof AiEndpointHealthSnapshot) {
                throw AiRuntimeContractException::invalidValue('AI endpoint group health snapshots');
            }
        }

        usort(
            $endpointGroupHealthSnapshots,
            static fn (AiEndpointHealthSnapshot $left, AiEndpointHealthSnapshot $right): int => $left->endpointGroup->value <=> $right->endpointGroup->value
        );

        return $endpointGroupHealthSnapshots;
    }

    /**
     * @param list<string> $reasonCodes
     * @return list<string>
     */
    private function assertReasonCodes(array $reasonCodes): array
    {
        foreach ($reasonCodes as $reasonCode) {
            if (!is_string($reasonCode) || trim($reasonCode) === '') {
                throw AiRuntimeContractException::invalidValue('AI runtime reason codes');
            }
        }

        return $reasonCodes;
    }
}
