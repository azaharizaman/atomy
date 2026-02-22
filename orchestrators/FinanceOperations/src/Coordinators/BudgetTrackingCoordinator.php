<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Coordinators;

use Nexus\FinanceOperations\Contracts\BudgetAvailableRuleInterface;
use Nexus\FinanceOperations\Contracts\BudgetTrackingCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\BudgetVarianceProviderInterface;
use Nexus\FinanceOperations\DTOs\BudgetCheckRequest;
use Nexus\FinanceOperations\DTOs\BudgetCheckResult;
use Nexus\FinanceOperations\DTOs\BudgetVarianceRequest;
use Nexus\FinanceOperations\DTOs\BudgetVarianceResult;
use Nexus\FinanceOperations\DTOs\BudgetThresholdRequest;
use Nexus\FinanceOperations\DTOs\BudgetThresholdResult;
use Nexus\FinanceOperations\Services\BudgetMonitoringService;
use Nexus\FinanceOperations\Rules\BudgetAvailableRule;
use Nexus\FinanceOperations\Exceptions\BudgetTrackingException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for budget tracking operations.
 * 
 * This coordinator manages the flow of budget-related operations:
 * - Budget availability checks
 * - Variance analysis
 * - Threshold monitoring
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
final readonly class BudgetTrackingCoordinator implements BudgetTrackingCoordinatorInterface
{
    public function __construct(
        private BudgetMonitoringService $budgetService,
        private BudgetVarianceProviderInterface $budgetDataProvider,
        private BudgetAvailableRuleInterface $budgetAvailableRule,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'BudgetTrackingCoordinator';
    }

    /**
     * @inheritDoc
     */
    public function hasRequiredData(string $tenantId, string $periodId): bool
    {
        try {
            $budgetData = $this->budgetDataProvider->getBudgetData($tenantId, $periodId);
            $budgets = $budgetData['budgets'] ?? [];
            return count($budgets) > 0;
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
            'check_budget_available',
            'calculate_variances',
            'check_thresholds',
        ];
    }

    /**
     * @inheritDoc
     */
    public function checkBudgetAvailable(BudgetCheckRequest $request): BudgetCheckResult
    {
        $this->logger->info('Coordinating budget availability check', [
            'tenant_id' => $request->tenantId,
            'budget_id' => $request->budgetId,
            'amount' => $request->amount,
        ]);

        try {
            // Validate budget availability using rule
            $ruleResult = $this->budgetAvailableRule->check((object)[
                'tenantId' => $request->tenantId,
                'budgetId' => $request->budgetId,
                'amount' => (string)$request->amount,
                'costCenterId' => $request->options['cost_center_id'] ?? null,
            ]);

            // Short-circuit if rule validation fails
            if (!$ruleResult->passed) {
                $this->logger->warning('Budget availability rule failed', [
                    'budget_id' => $request->budgetId,
                    'violations' => $ruleResult->violations,
                ]);

                return new BudgetCheckResult(
                    success: false,
                    budgetId: $request->budgetId,
                    availableAmount: $ruleResult->violations[0]['available'] ?? '0',
                    isAvailable: false,
                    errorMessage: $ruleResult->message ?? 'Budget availability check failed',
                );
            }

            // Convert to service DTO
            $serviceRequest = new \Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetCheckRequest(
                tenantId: $request->tenantId,
                budgetId: $request->budgetId,
                amount: (string)$request->amount,
                costCenterId: $request->options['cost_center_id'] ?? null,
                accountId: $request->options['account_id'] ?? null,
            );

            // Get detailed availability from service
            $serviceResult = $this->budgetService->checkAvailability($serviceRequest);

            // Convert back to interface DTO
            $result = new BudgetCheckResult(
                success: true,
                budgetId: $serviceResult->budgetId,
                availableAmount: $serviceResult->availableAmount,
                isAvailable: $serviceResult->available,
            );

            // Dispatch event if budget is exceeded
            if (!$serviceResult->available) {
                $this->eventDispatcher?->dispatch(new class(
                    $request->tenantId,
                    $request->budgetId,
                    (string)$request->amount,
                    $serviceResult->availableAmount
                ) {
                    public function __construct(
                        public string $tenantId,
                        public string $budgetId,
                        public string $requestedAmount,
                        public string $availableAmount,
                    ) {}
                });
            }

            return $result;
        } catch (BudgetTrackingException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Budget check coordination failed', [
                'tenant_id' => $request->tenantId,
                'budget_id' => $request->budgetId,
                'error' => $e->getMessage(),
            ]);

            throw BudgetTrackingException::checkFailed(
                $request->tenantId,
                $request->budgetId,
                $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function calculateVariances(BudgetVarianceRequest $request): BudgetVarianceResult
    {
        $this->logger->info('Coordinating variance calculation', [
            'tenant_id' => $request->tenantId,
            'period_id' => $request->periodId,
        ]);

        try {
            // Convert to service DTO
            $serviceRequest = new \Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetVarianceRequest(
                tenantId: $request->tenantId,
                periodId: $request->periodId,
                budgetId: $request->options['budget_id'] ?? null,
            );

            // Delegate to service
            $serviceResult = $this->budgetService->calculateVariances($serviceRequest);

            // Convert back to interface DTO
            $result = new BudgetVarianceResult(
                success: $serviceResult->success,
                periodId: $request->periodId,
                variances: $serviceResult->variances,
            );

            // Dispatch event
            $this->eventDispatcher?->dispatch(new class(
                $request->tenantId,
                $request->periodId,
                $serviceResult->totalVariance
            ) {
                public function __construct(
                    public string $tenantId,
                    public string $periodId,
                    public string $totalVariance,
                ) {}
            });

            return $result;
        } catch (BudgetTrackingException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Variance calculation coordination failed', [
                'tenant_id' => $request->tenantId,
                'period_id' => $request->periodId,
                'error' => $e->getMessage(),
            ]);

            throw BudgetTrackingException::varianceCalculationFailed(
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
    public function checkThresholds(BudgetThresholdRequest $request): BudgetThresholdResult
    {
        $this->logger->info('Coordinating threshold check', [
            'tenant_id' => $request->tenantId,
            'budget_id' => $request->budgetId,
        ]);

        try {
            // Convert to service DTO
            $serviceRequest = new \Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetThresholdRequest(
                tenantId: $request->tenantId,
                periodId: $request->options['period_id'] ?? '',
                thresholds: $request->options['thresholds'] ?? [80, 90, 100],
                costCenterId: $request->options['cost_center_id'] ?? null,
            );

            // Delegate to service
            $serviceResult = $this->budgetService->checkThresholds($serviceRequest);

            // Convert back to interface DTO
            $result = new BudgetThresholdResult(
                success: $serviceResult->success,
                budgetId: $request->budgetId,
                thresholds: $request->options['thresholds'] ?? [80, 90, 100],
                alerts: $serviceResult->warnings,
            );

            // Dispatch events for exceeded thresholds
            if (!empty($serviceResult->exceededThresholds)) {
                foreach ($serviceResult->exceededThresholds as $exceeded) {
                    $this->eventDispatcher?->dispatch(new class(
                        $request->tenantId,
                        $exceeded['budgetId'] ?? $request->budgetId,
                        $exceeded['threshold'],
                        $exceeded['utilizationPercent']
                    ) {
                        public function __construct(
                            public string $tenantId,
                            public string $budgetId,
                            public float $threshold,
                            public float $actualPercent,
                        ) {}
                    });
                }
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Threshold check coordination failed', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            return new BudgetThresholdResult(
                success: false,
                budgetId: $request->budgetId,
                thresholds: [],
                alerts: [],
                errorMessage: $e->getMessage(),
            );
        }
    }
}
