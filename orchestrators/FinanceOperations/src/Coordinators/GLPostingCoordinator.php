<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Coordinators;

use Nexus\FinanceOperations\Contracts\GLAccountMappingRuleInterface;
use Nexus\FinanceOperations\Contracts\GLPostingCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\GLReconciliationProviderInterface;
use Nexus\FinanceOperations\Contracts\SubledgerClosedRuleInterface;
use Nexus\FinanceOperations\DTOs\GLPostingRequest;
use Nexus\FinanceOperations\DTOs\GLPostingResult;
use Nexus\FinanceOperations\DTOs\GLReconciliationRequest;
use Nexus\FinanceOperations\DTOs\GLReconciliationResult;
use Nexus\FinanceOperations\DTOs\ConsistencyCheckRequest;
use Nexus\FinanceOperations\DTOs\ConsistencyCheckResult;
use Nexus\FinanceOperations\DTOs\RuleContexts\GLAccountMappingRuleContext;
use Nexus\FinanceOperations\DTOs\RuleContexts\SubledgerClosedRuleContext;
use Nexus\FinanceOperations\Enums\SubledgerType;
use Nexus\FinanceOperations\Services\GLReconciliationService;
use Nexus\FinanceOperations\Exceptions\GLReconciliationException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for GL posting operations.
 * 
 * This coordinator manages the flow of GL-related operations:
 * - Subledger to GL posting
 * - Reconciliation
 * - Consistency checks
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
final readonly class GLPostingCoordinator implements GLPostingCoordinatorInterface
{
    public function __construct(
        private GLReconciliationService $reconciliationService,
        private GLReconciliationProviderInterface $reconciliationProvider,
        private SubledgerClosedRuleInterface $subledgerClosedRule,
        private GLAccountMappingRuleInterface $accountMappingRule,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'GLPostingCoordinator';
    }

    /**
     * @inheritDoc
     */
    public function hasRequiredData(string $tenantId, string $periodId): bool
    {
        try {
            $status = $this->reconciliationProvider->getReconciliationStatus($tenantId, $periodId);
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
            'post_to_gl',
            'reconcile_with_gl',
            'validate_consistency',
        ];
    }

    /**
     * @inheritDoc
     */
    public function postToGL(GLPostingRequest $request): GLPostingResult
    {
        $this->logger->info('Coordinating GL posting', [
            'tenant_id' => $request->tenantId,
            'period_id' => $request->periodId,
            'subledger_type' => $request->subledgerType->value,
        ]);

        try {
            // Validate subledger is closed
            $closedResult = $this->subledgerClosedRule->check(new SubledgerClosedRuleContext(
                tenantId: $request->tenantId,
                periodId: $request->periodId,
                subledgerType: $request->subledgerType
            ));

            if (!$closedResult->passed) {
                throw GLReconciliationException::subledgerNotClosed(
                    $request->tenantId,
                    $request->periodId,
                    $request->subledgerType->value
                );
            }

            // Validate account mappings
            /** @var list<string> $transactionTypes */
            $transactionTypes = array_values(
                array_filter(
                    (array) ($request->options['transaction_types'] ?? []),
                    static fn (mixed $transactionType): bool => is_string($transactionType) && trim($transactionType) !== ''
                )
            );
            $mappingResult = $this->accountMappingRule->check(new GLAccountMappingRuleContext(
                tenantId: $request->tenantId,
                subledgerType: $request->subledgerType,
                transactionTypes: $transactionTypes
            ));

            if (!$mappingResult->passed) {
                throw GLReconciliationException::invalidAccountMapping(
                    $request->tenantId,
                    $request->subledgerType->value,
                    'mapping_validation_failed'
                );
            }

            // If validate only, return success without posting
            $validateOnly = $request->options['validate_only'] ?? false;
            if ($validateOnly) {
                return new GLPostingResult(
                    success: true,
                    periodId: $request->periodId,
                    entryCount: 0,
                    totalAmount: 0.0,
                    journalEntryIds: [],
                );
            }

            // Get subledger balance
            $balance = $this->reconciliationProvider->getSubledgerBalance(
                $request->tenantId,
                $request->periodId,
                $request->subledgerType->value
            );

            $postingId = 'POST-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
            $totalAmount = (float)($balance['balance'] ?? 0);
            $entryCount = (int)($balance['transaction_count'] ?? 1);

            // Dispatch event
            $this->eventDispatcher?->dispatch(new class(
                $request->tenantId,
                $request->periodId,
                $request->subledgerType->value,
                $postingId
            ) {
                public function __construct(
                    public string $tenantId,
                    public string $periodId,
                    public string $subledgerType,
                    public string $postingId,
                ) {}
            });

            return new GLPostingResult(
                success: true,
                periodId: $request->periodId,
                entryCount: $entryCount,
                totalAmount: $totalAmount,
                journalEntryIds: ['JE-' . $postingId],
            );
        } catch (GLReconciliationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('GL posting coordination failed', [
                'tenant_id' => $request->tenantId,
                'subledger_type' => $request->subledgerType->value,
                'error' => $e->getMessage(),
            ]);

            throw GLReconciliationException::postingFailed(
                $request->tenantId,
                $request->subledgerType->value,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function reconcileWithGL(GLReconciliationRequest $request): GLReconciliationResult
    {
        try {
            $subledgerType = SubledgerType::fromString($request->subledgerType);
        } catch (\InvalidArgumentException $e) {
            throw GLReconciliationException::invalidAccountMapping(
                $request->tenantId,
                $request->subledgerType,
                'invalid_subledger_type'
            );
        }

        $this->logger->info('Coordinating GL reconciliation', [
            'tenant_id' => $request->tenantId,
            'period_id' => $request->periodId,
            'subledger_type' => $subledgerType->value,
        ]);

        try {
            // Convert to service DTO
            $serviceRequest = new \Nexus\FinanceOperations\DTOs\GLPosting\GLReconciliationRequest(
                tenantId: $request->tenantId,
                periodId: $request->periodId,
                subledgerType: $subledgerType,
                autoAdjust: $request->options['auto_adjust'] ?? false,
            );

            // Delegate to service
            $serviceResult = $this->reconciliationService->reconcile($serviceRequest);

            // Convert back to interface DTO
            $result = new GLReconciliationResult(
                success: $serviceResult->success,
                periodId: $request->periodId,
                subledgerBalance: (float)$serviceResult->subledgerBalance,
                glBalance: (float)$serviceResult->glBalance,
                difference: (float)$serviceResult->variance,
                discrepancies: $serviceResult->discrepancies,
            );

            // Dispatch event
            $this->eventDispatcher?->dispatch(new class(
                $request->tenantId,
                $subledgerType->value,
                $result->success
            ) {
                public function __construct(
                    public string $tenantId,
                    public string $subledgerType,
                    public bool $isReconciled,
                ) {}
            });

            return $result;
        } catch (GLReconciliationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('GL reconciliation coordination failed', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            throw GLReconciliationException::reconciliationMismatch(
                $request->tenantId,
                $subledgerType->value,
                '0',
                '0',
                $e->getMessage()
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function validateConsistency(ConsistencyCheckRequest $request): ConsistencyCheckResult
    {
        $this->logger->info('Coordinating consistency check', [
            'tenant_id' => $request->tenantId,
            'period_id' => $request->periodId,
        ]);

        try {
            /** @var list<SubledgerType> $subledgerTypes */
            $subledgerTypes = [];
            foreach ((array) ($request->options['subledger_types'] ?? []) as $value) {
                if (!is_string($value) || trim($value) === '') {
                    continue;
                }

                try {
                    $subledgerTypes[] = SubledgerType::fromString($value);
                } catch (\InvalidArgumentException) {
                    // Ignore unknown subledger values from caller input.
                }
            }

            // Convert to service DTO
            $serviceRequest = new \Nexus\FinanceOperations\DTOs\GLPosting\ConsistencyCheckRequest(
                tenantId: $request->tenantId,
                periodId: $request->periodId,
                subledgerTypes: $subledgerTypes !== []
                    ? $subledgerTypes
                    : [SubledgerType::RECEIVABLE, SubledgerType::PAYABLE, SubledgerType::ASSET],
            );

            // Delegate to service
            $serviceResult = $this->reconciliationService->checkConsistency($serviceRequest);

            // Convert back to interface DTO
            $result = new ConsistencyCheckResult(
                success: $serviceResult->success,
                isConsistent: $serviceResult->allConsistent,
                issues: $serviceResult->inconsistencies,
            );

            // Dispatch event
            $this->eventDispatcher?->dispatch(new class(
                $request->tenantId,
                $request->periodId,
                $result->isConsistent
            ) {
                public function __construct(
                    public string $tenantId,
                    public string $periodId,
                    public bool $allConsistent,
                ) {}
            });

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Consistency check coordination failed', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            throw GLReconciliationException::consistencyCheckFailed(
                $request->tenantId,
                $request->periodId,
                []
            );
        }
    }
}
