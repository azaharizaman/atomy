<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Services;

use Nexus\FinanceOperations\Contracts\GLReconciliationProviderInterface;
use Nexus\FinanceOperations\DTOs\GLPosting\GLReconciliationRequest;
use Nexus\FinanceOperations\DTOs\GLPosting\GLReconciliationResult;
use Nexus\FinanceOperations\DTOs\GLPosting\ConsistencyCheckRequest;
use Nexus\FinanceOperations\DTOs\GLPosting\ConsistencyCheckResult;
use Nexus\FinanceOperations\Exceptions\GLReconciliationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for GL reconciliation operations.
 * 
 * This service handles:
 * - Subledger to GL reconciliation
 * - Discrepancy identification
 * - Consistency checks across subledgers
 * 
 * Following Advanced Orchestrator Pattern v1.1:
 * Services handle the "heavy lifting" - calculations and cross-boundary logic.
 * 
 * @since 1.0.0
 */
final readonly class GLReconciliationService
{
    public function __construct(
        private GLReconciliationProviderInterface $dataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Reconcile subledger with GL.
     *
     * @param GLReconciliationRequest $request The reconciliation request parameters
     * @return GLReconciliationResult The reconciliation result
     * @throws GLReconciliationException If reconciliation fails
     */
    public function reconcile(GLReconciliationRequest $request): GLReconciliationResult
    {
        $this->logger->info('Starting GL reconciliation', [
            'tenant_id' => $request->tenantId,
            'period_id' => $request->periodId,
            'subledger_type' => $request->subledgerType,
        ]);

        try {
            // Get subledger balance
            $subledgerData = $this->dataProvider->getSubledgerBalance(
                $request->tenantId,
                $request->periodId,
                $request->subledgerType
            );

            // Get GL control account balance
            $controlAccountId = $this->getControlAccountForSubledger($request->subledgerType);
            $glData = $this->dataProvider->getGLBalance(
                $request->tenantId,
                $request->periodId,
                $controlAccountId
            );

            $subledgerBalance = $subledgerData['balance'] ?? '0';
            $glBalance = $glData['balance'] ?? '0';
            $variance = (string)((float)$subledgerBalance - (float)$glBalance);

            $isReconciled = abs((float)$variance) < 0.01;

            if (!$isReconciled && $request->autoAdjust) {
                // Attempt automatic adjustment
                $adjustmentResult = $this->createAdjustingEntries(
                    $request->tenantId,
                    $request->periodId,
                    $request->subledgerType,
                    $subledgerBalance,
                    $glBalance,
                    $variance
                );

                return new GLReconciliationResult(
                    success: $adjustmentResult['success'],
                    subledgerType: $request->subledgerType,
                    subledgerBalance: $subledgerBalance,
                    glBalance: $glBalance,
                    variance: '0', // After adjustment
                    discrepancies: [],
                );
            }

            // Get discrepancies if not reconciled
            $discrepancies = [];
            if (!$isReconciled) {
                $discrepancyData = $this->dataProvider->getDiscrepancies(
                    $request->tenantId,
                    $request->periodId
                );
                $discrepancies = $this->filterDiscrepanciesByType(
                    $discrepancyData['discrepancies'] ?? [],
                    $request->subledgerType
                );
            }

            return new GLReconciliationResult(
                success: $isReconciled,
                subledgerType: $request->subledgerType,
                subledgerBalance: $subledgerBalance,
                glBalance: $glBalance,
                variance: $variance,
                discrepancies: $discrepancies,
            );
        } catch (GLReconciliationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('GL reconciliation failed', [
                'tenant_id' => $request->tenantId,
                'subledger_type' => $request->subledgerType,
                'error' => $e->getMessage(),
            ]);

            throw GLReconciliationException::reconciliationMismatch(
                $request->tenantId,
                $request->subledgerType,
                '0',
                '0',
                $e->getMessage()
            );
        }
    }

    /**
     * Check consistency across all subledgers.
     *
     * @param ConsistencyCheckRequest $request The consistency check request parameters
     * @return ConsistencyCheckResult The consistency check result
     */
    public function checkConsistency(ConsistencyCheckRequest $request): ConsistencyCheckResult
    {
        $this->logger->info('Checking GL consistency', [
            'tenant_id' => $request->tenantId,
            'period_id' => $request->periodId,
            'subledger_types' => implode(', ', $request->subledgerTypes),
        ]);

        try {
            $status = $this->dataProvider->getReconciliationStatus(
                $request->tenantId,
                $request->periodId
            );

            $checks = [];
            $allConsistent = true;
            $inconsistencies = [];

            foreach ($request->subledgerTypes as $type) {
                $typeStatus = $status['details'][$type] ?? $status[$type] ?? null;
                
                if ($typeStatus === null) {
                    $checks[$type] = [
                        'subledgerType' => $type,
                        'consistent' => false,
                        'subledgerBalance' => '0',
                        'glBalance' => '0',
                        'variance' => null,
                    ];
                    $allConsistent = false;
                    continue;
                }

                $isReconciled = $typeStatus['is_reconciled'] ?? ($typeStatus['consistent'] ?? false);
                $variance = $typeStatus['variance'] ?? '0';
                
                $checks[$type] = [
                    'subledgerType' => $type,
                    'consistent' => $isReconciled,
                    'subledgerBalance' => $typeStatus['subledger_balance'] ?? '0',
                    'glBalance' => $typeStatus['gl_balance'] ?? '0',
                    'variance' => $variance,
                ];

                if (!$isReconciled) {
                    $allConsistent = false;
                    $inconsistencies[] = [
                        'subledgerType' => $type,
                        'type' => 'variance',
                        'description' => sprintf('Variance of %s detected', $variance),
                        'amount' => $variance,
                    ];
                }
            }

            return new ConsistencyCheckResult(
                success: true,
                checks: $checks,
                allConsistent: $allConsistent,
                inconsistencies: $inconsistencies,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Consistency check failed', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            return new ConsistencyCheckResult(
                success: false,
                checks: [],
                allConsistent: false,
                inconsistencies: [],
                error: $e->getMessage(),
            );
        }
    }

    /**
     * Get reconciliation status for a period.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @return array<string, mixed> Reconciliation status data
     */
    public function getReconciliationStatus(string $tenantId, string $periodId): array
    {
        $this->logger->info('Getting reconciliation status', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
        ]);

        return $this->dataProvider->getReconciliationStatus($tenantId, $periodId);
    }

    /**
     * Get control account for subledger type.
     *
     * @param string $subledgerType The subledger type
     * @return string Control account code
     */
    private function getControlAccountForSubledger(string $subledgerType): string
    {
        return match ($subledgerType) {
            'receivable', 'AR' => 'AR_CONTROL',
            'payable', 'AP' => 'AP_CONTROL',
            'asset', 'FA' => 'ASSET_CONTROL',
            'inventory', 'INV' => 'INVENTORY_CONTROL',
            default => strtoupper($subledgerType) . '_CONTROL',
        };
    }

    /**
     * Filter discrepancies by subledger type.
     *
     * @param array<int, array<string, mixed>> $discrepancies All discrepancies
     * @param string $subledgerType Subledger type to filter by
     * @return array<int, array<string, mixed>> Filtered discrepancies
     */
    private function filterDiscrepanciesByType(array $discrepancies, string $subledgerType): array
    {
        return array_values(array_filter(
            $discrepancies,
            fn($d) => ($d['subledger_type'] ?? $d['type'] ?? '') === $subledgerType
        ));
    }

    /**
     * Create adjusting entries for discrepancies.
     *
     * Note: Automatic GL adjustments require proper controls and audit trails.
     * This implementation creates an adjustment proposal that requires approval
     * before being posted to the GL. In production, this would integrate with
     * a journal entry approval workflow.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The period identifier
     * @param string $subledgerType The subledger type
     * @param string $subledgerBalance Subledger balance
     * @param string $glBalance GL balance
     * @param string $variance The variance amount
     * @return array<string, mixed> Adjustment result
     */
    private function createAdjustingEntries(
        string $tenantId,
        string $periodId,
        string $subledgerType,
        string $subledgerBalance,
        string $glBalance,
        string $variance
    ): array {
        $this->logger->warning('Creating adjusting entries', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
            'subledger_type' => $subledgerType,
            'variance' => $variance,
        ]);

        // Determine the adjustment direction
        $varianceAmount = (float)$variance;
        $isDebitAdjustment = $varianceAmount > 0; // Subledger > GL means we need to debit GL

        // Get the control account for the subledger
        $controlAccountId = $this->getControlAccountForSubledger($subledgerType);

        // Create adjustment journal entry lines
        // Debit/Credit the difference to balance the GL to the subledger
        $adjustmentLines = [];
        
        if ($isDebitAdjustment) {
            // Subledger shows more than GL - debit the control account to increase it
            $adjustmentLines[] = [
                'line_number' => 1,
                'account_type' => 'control',
                'account_id' => $controlAccountId,
                'debit' => abs($varianceAmount),
                'credit' => 0,
                'description' => sprintf(
                    'Reconciliation adjustment - %s subledger to GL variance',
                    $subledgerType
                ),
            ];
            // Credit to suspense/reconciliation account
            $adjustmentLines[] = [
                'line_number' => 2,
                'account_type' => 'suspense',
                'account_id' => '9999', // Suspense account
                'debit' => 0,
                'credit' => abs($varianceAmount),
                'description' => sprintf(
                    'Reconciliation suspense - %s variance',
                    $subledgerType
                ),
            ];
        } else {
            // Subledger shows less than GL - credit the control account to decrease it
            $adjustmentLines[] = [
                'line_number' => 1,
                'account_type' => 'control',
                'account_id' => $controlAccountId,
                'debit' => 0,
                'credit' => abs($varianceAmount),
                'description' => sprintf(
                    'Reconciliation adjustment - %s subledger to GL variance',
                    $subledgerType
                ),
            ];
            // Debit from suspense/reconciliation account
            $adjustmentLines[] = [
                'line_number' => 2,
                'account_type' => 'suspense',
                'account_id' => '9999', // Suspense account
                'debit' => abs($varianceAmount),
                'credit' => 0,
                'description' => sprintf(
                    'Reconciliation suspense - %s variance',
                    $subledgerType
                ),
            ];
        }

        // Generate adjustment proposal ID
        $adjustmentId = sprintf('ADJ-%s-%s-%s', 
            strtoupper($subledgerType), 
            $periodId, 
            bin2hex(random_bytes(4))
        );

        $result = [
            'success' => true,
            'adjustment_id' => $adjustmentId,
            'entries_created' => count($adjustmentLines),
            'adjustment_amount' => $variance,
            'adjustment_direction' => $isDebitAdjustment ? 'debit' : 'credit',
            'control_account' => $controlAccountId,
            'status' => 'pending_approval',
            'journal_lines' => $adjustmentLines,
            'message' => sprintf(
                'Adjustment proposal created for %s variance. Requires approval before posting.',
                $subledgerType
            ),
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'requires_approval' => true,
            'approved_by' => null,
            'posted_by' => null,
        ];

        $this->logger->info('Adjustment proposal created', [
            'adjustment_id' => $adjustmentId,
            'amount' => $variance,
            'status' => 'pending_approval',
        ]);

        return $result;
    }
}
