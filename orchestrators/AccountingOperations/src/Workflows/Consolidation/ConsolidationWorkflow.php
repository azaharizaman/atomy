<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\Consolidation;

use Nexus\AccountingOperations\Contracts\AccountingWorkflowInterface;
use Nexus\AccountingOperations\Coordinators\ConsolidationCoordinator;
use Nexus\AccountingOperations\DTOs\ConsolidationRequest;
use Nexus\AccountConsolidation\ValueObjects\ConsolidationResult;
use Nexus\AccountingOperations\Exceptions\WorkflowException;

/**
 * Workflow for consolidated financial statements
 */
final readonly class ConsolidationWorkflow implements AccountingWorkflowInterface
{
    public function __construct(
        private ConsolidationCoordinator $coordinator
    ) {}

    /**
     * Execute the consolidation workflow
     *
     * @param ConsolidationRequest $request
     * @return array{result: ConsolidationResult, metadata: array<string, mixed>}
     * @throws WorkflowException
     */
    public function execute(object $request): array
    {
        if (!$request instanceof ConsolidationRequest) {
            throw new WorkflowException('Invalid request type. Expected ConsolidationRequest.');
        }

        try {
            $startTime = microtime(true);

            // Execute consolidation
            $result = $this->coordinator->consolidate(
                tenantId: $request->tenantId,
                parentEntityId: $request->parentEntityId,
                periodId: $request->periodId,
                subsidiaryIds: $request->subsidiaryIds,
                consolidationMethod: $request->consolidationMethod,
                translationMethod: $request->translationMethod,
                eliminateIntercompany: $request->eliminateIntercompany
            );

            $endTime = microtime(true);

            return [
                'result' => $result,
                'metadata' => [
                    'generated_at' => new \DateTimeImmutable(),
                    'generation_time_ms' => ($endTime - $startTime) * 1000,
                    'period_id' => $request->periodId,
                    'parent_entity_id' => $request->parentEntityId,
                    'subsidiary_count' => count($request->subsidiaryIds),
                    'consolidation_method' => $request->consolidationMethod->value,
                    'translation_method' => $request->translationMethod?->value,
                    'eliminations_count' => $result->eliminationsCount,
                    'translation_adjustments_count' => $result->translationAdjustmentsCount,
                ],
            ];
        } catch (\Throwable $e) {
            if ($e instanceof WorkflowException) {
                throw $e;
            }
            throw new WorkflowException(
                "Consolidation failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Get workflow name
     */
    public function getName(): string
    {
        return 'consolidation';
    }

    /**
     * Get workflow description
     */
    public function getDescription(): string
    {
        return 'Consolidates financial statements from multiple entities with eliminations and currency translation';
    }
}
