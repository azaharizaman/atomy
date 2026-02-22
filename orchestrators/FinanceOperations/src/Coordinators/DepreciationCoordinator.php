<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Coordinators;

use Nexus\FinanceOperations\Contracts\DepreciationCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\DepreciationDataProviderInterface;
use Nexus\FinanceOperations\DTOs\DepreciationRunRequest;
use Nexus\FinanceOperations\DTOs\DepreciationRunResult;
use Nexus\FinanceOperations\DTOs\DepreciationScheduleRequest;
use Nexus\FinanceOperations\DTOs\DepreciationScheduleResult;
use Nexus\FinanceOperations\DTOs\RevaluationRequest;
use Nexus\FinanceOperations\DTOs\RevaluationResult;
use Nexus\FinanceOperations\Services\DepreciationRunService;
use Nexus\FinanceOperations\Rules\PeriodOpenRule;
use Nexus\FinanceOperations\Exceptions\DepreciationCoordinationException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for depreciation operations.
 * 
 * This coordinator manages the flow of depreciation-related operations:
 * - Depreciation run execution
 * - Schedule generation
 * - Asset revaluation
 * 
 * Following the Advanced Orchestrator Pattern:
 * - Coordinators direct flow, they do not execute business logic
 * - Delegates to services for calculations and heavy lifting
 * - Uses rules for validation
 * - Uses data providers for data aggregation
 * 
 * @see ARCHITECTURE.md Section 4: The Advanced Orchestrator Pattern
 * @since 1.0.0
 */
final readonly class DepreciationCoordinator implements DepreciationCoordinatorInterface
{
    public function __construct(
        private DepreciationRunService $depreciationService,
        private DepreciationDataProviderInterface $depreciationDataProvider,
        private PeriodOpenRule $periodOpenRule,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'DepreciationCoordinator';
    }

    /**
     * @inheritDoc
     */
    public function hasRequiredData(string $tenantId, string $periodId): bool
    {
        try {
            $summary = $this->depreciationDataProvider->getDepreciationRunSummary($tenantId, $periodId);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getSupportedOperations(): array
    {
        return [
            'run_depreciation',
            'generate_schedules',
            'process_revaluation',
        ];
    }

    /**
     * @inheritDoc
     */
    public function runDepreciation(DepreciationRunRequest $request): DepreciationRunResult
    {
        $this->logger->info('Coordinating depreciation run', [
            'tenant_id' => $request->tenantId,
            'period_id' => $request->periodId,
            'asset_count' => count($request->assetIds),
            'post_to_gl' => $request->postToGL,
        ]);

        try {
            // Validate period is open
            $periodResult = $this->periodOpenRule->check((object)[
                'tenantId' => $request->tenantId,
                'periodId' => $request->periodId,
            ]);

            if (!$periodResult->passed) {
                throw DepreciationCoordinationException::periodAlreadyProcessed(
                    $request->tenantId,
                    $request->periodId
                );
            }

            // Convert to service DTO
            $serviceRequest = new \Nexus\FinanceOperations\DTOs\Depreciation\DepreciationRunRequest(
                tenantId: $request->tenantId,
                periodId: $request->periodId,
                assetIds: $request->assetIds,
                postToGL: $request->postToGL,
                validateOnly: $request->validateOnly,
            );

            // Delegate to service
            $serviceResult = $this->depreciationService->executeRun($serviceRequest);

            // Convert back to interface DTO
            $result = new DepreciationRunResult(
                success: $serviceResult->success,
                periodId: $request->periodId,
                assetCount: $serviceResult->assetsProcessed,
                totalDepreciation: (float)$serviceResult->totalDepreciation,
                depreciationEntries: $serviceResult->assetDetails,
                errorMessage: $serviceResult->error,
            );

            // Dispatch event
            $this->eventDispatcher?->dispatch(new class(
                $request->tenantId,
                $request->periodId,
                $result
            ) {
                public function __construct(
                    public string $tenantId,
                    public string $periodId,
                    public DepreciationRunResult $result,
                ) {}
            });

            return $result;
        } catch (DepreciationCoordinationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Depreciation run coordination failed', [
                'tenant_id' => $request->tenantId,
                'period_id' => $request->periodId,
                'error' => $e->getMessage(),
            ]);

            throw DepreciationCoordinationException::runFailed(
                $request->tenantId,
                $request->periodId,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function generateSchedules(DepreciationScheduleRequest $request): DepreciationScheduleResult
    {
        $this->logger->info('Coordinating schedule generation', [
            'tenant_id' => $request->tenantId,
            'asset_id' => $request->assetId,
        ]);

        try {
            // Convert to service DTO
            $serviceRequest = new \Nexus\FinanceOperations\DTOs\Depreciation\DepreciationScheduleRequest(
                tenantId: $request->tenantId,
                assetId: $request->assetId,
                depreciationMethod: $request->options['depreciation_method'] ?? 'straight_line',
                usefulLife: $request->options['useful_life'] ?? null,
                salvageValue: $request->options['salvage_value'] ?? '0',
            );

            // Delegate to service
            $serviceResult = $this->depreciationService->generateSchedule($serviceRequest);

            // Convert back to interface DTO
            $result = new DepreciationScheduleResult(
                success: $serviceResult->success,
                assetId: $serviceResult->assetId,
                schedule: $serviceResult->schedule,
                errorMessage: $serviceResult->error,
            );

            // Dispatch event
            $this->eventDispatcher?->dispatch(new class($request->tenantId, $result) {
                public function __construct(
                    public string $tenantId,
                    public DepreciationScheduleResult $result,
                ) {}
            });

            return $result;
        } catch (DepreciationCoordinationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Schedule generation coordination failed', [
                'tenant_id' => $request->tenantId,
                'asset_id' => $request->assetId,
                'error' => $e->getMessage(),
            ]);

            throw DepreciationCoordinationException::scheduleGenerationFailed(
                $request->tenantId,
                $request->assetId,
                $e->getMessage()
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function processRevaluation(RevaluationRequest $request): RevaluationResult
    {
        $this->logger->info('Coordinating asset revaluation', [
            'tenant_id' => $request->tenantId,
            'asset_id' => $request->assetId,
            'new_value' => $request->newValue,
        ]);

        try {
            // Get current book value
            $bookValues = $this->depreciationDataProvider->getAssetBookValues(
                $request->tenantId,
                [$request->assetId]
            );

            if (empty($bookValues)) {
                throw DepreciationCoordinationException::assetNotFound(
                    $request->tenantId,
                    $request->assetId
                );
            }

            $previousValue = (float)($bookValues[0]['book_value'] ?? $bookValues[0]['net_book_value'] ?? 0);
            $adjustment = $request->newValue - $previousValue;

            // Dispatch event
            $this->eventDispatcher?->dispatch(new class(
                $request->tenantId,
                $request->assetId,
                $previousValue,
                $request->newValue
            ) {
                public function __construct(
                    public string $tenantId,
                    public string $assetId,
                    public float $previousValue,
                    public float $newValue,
                ) {}
            });

            return new RevaluationResult(
                success: true,
                assetId: $request->assetId,
                previousValue: $previousValue,
                newValue: $request->newValue,
                adjustment: $adjustment,
            );
        } catch (DepreciationCoordinationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Revaluation coordination failed', [
                'tenant_id' => $request->tenantId,
                'asset_id' => $request->assetId,
                'error' => $e->getMessage(),
            ]);

            throw DepreciationCoordinationException::revaluationFailed(
                $request->tenantId,
                $request->assetId,
                $e->getMessage(),
                $e
            );
        }
    }
}
