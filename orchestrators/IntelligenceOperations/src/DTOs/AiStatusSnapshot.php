<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\DTOs;

use DateTimeImmutable;
use Nexus\IntelligenceOperations\Exceptions\AiStatusContractException;

final readonly class AiStatusSnapshot
{
    /**
     * @param array<int, AiCapabilityDefinition> $capabilityDefinitions
     * @param array<string, AiCapabilityStatus> $capabilityStatuses
     * @param array<int, AiEndpointHealthSnapshot> $endpointGroupHealthSnapshots
     * @param list<string> $reasonCodes
     */
    public function __construct(
        public string $mode,
        public string $globalHealth,
        array $capabilityDefinitions,
        array $capabilityStatuses,
        array $endpointGroupHealthSnapshots,
        array $reasonCodes,
        DateTimeImmutable $generatedAt,
    ) {
        AiStatusSchema::assertMode($this->mode);
        AiStatusSchema::assertHealth($this->globalHealth);
        $this->capabilityDefinitions = $this->normalizeCapabilityDefinitions($capabilityDefinitions);
        $this->capabilityStatuses = $this->normalizeCapabilityStatuses($capabilityStatuses);
        $this->endpointGroupHealthSnapshots = $this->normalizeEndpointGroupHealthSnapshots($endpointGroupHealthSnapshots);
        $this->reasonCodes = AiStatusSchema::sanitizeReasonCodes($reasonCodes);
        $this->generatedAt = $generatedAt;
    }

    /**
     * @var array<int, AiCapabilityDefinition>
     */
    public array $capabilityDefinitions;

    /**
     * @var array<string, AiCapabilityStatus>
     */
    public array $capabilityStatuses;

    /**
     * @var array<int, AiEndpointHealthSnapshot>
     */
    public array $endpointGroupHealthSnapshots;

    /**
     * @var list<string>
     */
    public array $reasonCodes;

    public DateTimeImmutable $generatedAt;

    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'global_health' => $this->globalHealth,
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
    private function normalizeCapabilityDefinitions(array $capabilityDefinitions): array
    {
        foreach ($capabilityDefinitions as $definition) {
            if (!$definition instanceof AiCapabilityDefinition) {
                throw AiStatusContractException::invalidValue('AI capability definitions');
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
                throw AiStatusContractException::invalidValue('AI capability status keys');
            }

            if (!$status instanceof AiCapabilityStatus) {
                throw AiStatusContractException::invalidValue('AI capability status entries');
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
    private function normalizeEndpointGroupHealthSnapshots(array $endpointGroupHealthSnapshots): array
    {
        $normalized = [];

        foreach ($endpointGroupHealthSnapshots as $snapshot) {
            if (!$snapshot instanceof AiEndpointHealthSnapshot) {
                throw AiStatusContractException::invalidValue('AI endpoint group health snapshots');
            }

            if (isset($normalized[$snapshot->endpointGroup])) {
                throw AiStatusContractException::invalidValue('AI endpoint group health snapshots');
            }

            $normalized[$snapshot->endpointGroup] = $snapshot;
        }

        ksort($normalized);

        return array_values($normalized);
    }
}
