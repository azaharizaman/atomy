<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\MultiEntityPaymentServiceInterface;
use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ProcurementOperations\DTOs\Financial\MultiEntityPaymentBatch;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Events\Financial\MultiEntityPaymentBatchCreatedEvent;
use Nexus\ProcurementOperations\Events\Financial\MultiEntityPaymentBatchExecutedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for multi-entity payment operations.
 *
 * Orchestrates payment processing across multiple legal entities with:
 * - Optimal bank account selection per entity
 * - Cross-entity authorization chains
 * - Consolidated reporting and analytics
 * - Entity-specific payment rules
 *
 * Follows the "traffic cop" pattern - delegates actual work to services.
 */
final readonly class MultiEntityPaymentCoordinator
{
    public function __construct(
        private MultiEntityPaymentServiceInterface $paymentService,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger(),
        private ?SecureIdGeneratorInterface $idGenerator = null,
    ) {}

    /**
     * Create payment batch for entity.
     *
     * @param string $tenantId Tenant context
     * @param string $entityId Legal entity
     * @param array<PaymentItemData> $paymentItems Payments to include
     * @param string $paymentMethod Payment method (ACH, WIRE, CHECK)
     * @param string $createdBy User creating batch
     * @param array $options Additional options
     * @return array{batch: MultiEntityPaymentBatch, warnings: array}
     */
    public function createPaymentBatch(
        string $tenantId,
        string $entityId,
        array $paymentItems,
        string $paymentMethod,
        string $createdBy,
        array $options = [],
    ): array {
        $this->logger->info('Creating payment batch for entity', [
            'tenant_id' => $tenantId,
            'entity_id' => $entityId,
            'payment_count' => count($paymentItems),
            'method' => $paymentMethod,
        ]);

        $warnings = [];

        // Validate payment items
        $validatedItems = [];
        foreach ($paymentItems as $item) {
            $validation = $this->validatePaymentItem($entityId, $item, $paymentMethod);
            if ($validation['valid']) {
                $validatedItems[] = $item;
            } else {
                $warnings[] = "Payment {$item->paymentItemId}: {$validation['reason']}";
            }
        }

        if (empty($validatedItems)) {
            throw new \DomainException('No valid payment items for batch');
        }

        // Calculate totals
        $totalAmount = $this->calculateTotalAmount($validatedItems);
        $currency = $validatedItems[0]->amount->getCurrency();

        // Select optimal bank account
        $bankSelection = $this->paymentService->selectOptimalBank(
            $entityId,
            $totalAmount,
            $paymentMethod,
        );

        // Create batch
        $batch = new MultiEntityPaymentBatch(
            batchId: $this->generateBatchId(),
            entityId: $entityId,
            entityName: $options['entity_name'] ?? 'Entity ' . $entityId,
            bankId: $bankSelection['bank_id'],
            bankAccountNumber: $bankSelection['account_number'],
            totalAmount: $totalAmount,
            currency: $currency,
            paymentMethod: $paymentMethod,
            paymentItems: $validatedItems,
            executionDate: $options['execution_date'] ?? new \DateTimeImmutable('+1 day'),
            metadata: [
                'created_by' => $createdBy,
                'created_at' => (new \DateTimeImmutable())->format('c'),
            ],
        );

        // Dispatch creation event
        $this->eventDispatcher->dispatch(new MultiEntityPaymentBatchCreatedEvent(
            batchId: $batch->batchId,
            entityId: $entityId,
            entityName: $batch->entityName,
            paymentCount: $batch->getPaymentCount(),
            totalAmount: $totalAmount,
            paymentMethod: $paymentMethod,
            createdBy: $createdBy,
        ));

        $this->logger->info('Payment batch created', [
            'batch_id' => $batch->batchId,
            'total_amount' => $totalAmount->getAmount(),
            'payment_count' => $batch->getPaymentCount(),
        ]);

        return [
            'batch' => $batch,
            'warnings' => $warnings,
            'bank_selected' => $bankSelection,
        ];
    }

    /**
     * Get approval chain for batch.
     *
     * @param MultiEntityPaymentBatch $batch Batch to get approval chain for
     * @return array{levels: array, required_approvals: int, current_level: int}
     */
    public function getApprovalChain(MultiEntityPaymentBatch $batch): array
    {
        $chain = $this->paymentService->getAuthorizationChain(
            $batch->entityId,
            $batch->totalAmount,
        );

        return [
            'batch_id' => $batch->batchId,
            'levels' => $chain,
            'required_approvals' => count($chain),
            'current_level' => 0,
        ];
    }

    /**
     * Execute approved payment batch.
     *
     * @param string $tenantId Tenant context
     * @param MultiEntityPaymentBatch $batch Batch to execute
     * @param string $executedBy Executing user
     * @return array Execution result with success/failure details
     */
    public function executeBatch(
        string $tenantId,
        MultiEntityPaymentBatch $batch,
        string $executedBy,
    ): array {
        $this->logger->info('Executing payment batch', [
            'batch_id' => $batch->batchId,
            'entity_id' => $batch->entityId,
            'payment_count' => $batch->getPaymentCount(),
        ]);

        // Validate batch is approved
        if (!$batch->isApproved()) {
            throw new \DomainException("Batch {$batch->batchId} is not approved");
        }

        // Execute through service
        $result = $this->paymentService->executePaymentBatch($batch);

        // Calculate totals from result
        $successfulPayments = $result->successfulPayments ?? 0;
        $failedPayments = $result->failedPayments ?? 0;
        $totalPaid = $result->totalPaid ?? Money::of(0, $batch->currency);
        $totalFailed = $result->totalFailed ?? Money::of(0, $batch->currency);

        // Dispatch execution event
        $this->eventDispatcher->dispatch(new MultiEntityPaymentBatchExecutedEvent(
            batchId: $batch->batchId,
            entityId: $batch->entityId,
            successfulPayments: $successfulPayments,
            failedPayments: $failedPayments,
            totalPaid: $totalPaid,
            totalFailed: $totalFailed,
            executedBy: $executedBy,
        ));

        $this->logger->info('Payment batch executed', [
            'batch_id' => $batch->batchId,
            'successful' => $successfulPayments,
            'failed' => $failedPayments,
        ]);

        return [
            'batch_id' => $batch->batchId,
            'status' => $failedPayments === 0 ? 'completed' : 'partial',
            'successful_payments' => $successfulPayments,
            'failed_payments' => $failedPayments,
            'total_paid' => $totalPaid,
            'total_failed' => $totalFailed,
            'executed_by' => $executedBy,
            'executed_at' => new \DateTimeImmutable(),
            'details' => $result->details ?? [],
        ];
    }

    /**
     * Create consolidated batch across multiple entities.
     *
     * @param string $tenantId Tenant context
     * @param array<string, array<PaymentItemData>> $entityPayments Payments by entity
     * @param string $paymentMethod Payment method
     * @param string $createdBy Creating user
     * @return array{batches: array<MultiEntityPaymentBatch>, summary: array}
     */
    public function createConsolidatedBatches(
        string $tenantId,
        array $entityPayments,
        string $paymentMethod,
        string $createdBy,
    ): array {
        $this->logger->info('Creating consolidated payment batches', [
            'tenant_id' => $tenantId,
            'entity_count' => count($entityPayments),
        ]);

        $batches = [];
        $warnings = [];
        $totalAmount = Money::of(0, 'USD');
        $totalPayments = 0;

        foreach ($entityPayments as $entityId => $payments) {
            if (empty($payments)) {
                continue;
            }

            try {
                $result = $this->createPaymentBatch(
                    $tenantId,
                    $entityId,
                    $payments,
                    $paymentMethod,
                    $createdBy,
                );

                $batches[] = $result['batch'];
                $warnings = array_merge($warnings, $result['warnings']);
                $totalAmount = $totalAmount->add($result['batch']->totalAmount);
                $totalPayments += $result['batch']->getPaymentCount();
            } catch (\Throwable $e) {
                $warnings[] = "Entity {$entityId}: {$e->getMessage()}";
            }
        }

        return [
            'batches' => $batches,
            'summary' => [
                'entity_count' => count($batches),
                'total_payments' => $totalPayments,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'created_by' => $createdBy,
                'created_at' => new \DateTimeImmutable(),
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * Get cross-entity payment statistics.
     *
     * @param string $tenantId Tenant context
     * @param array<string> $entityIds Entities to include
     * @param \DateTimeImmutable $periodStart Start date
     * @param \DateTimeImmutable $periodEnd End date
     * @return array Consolidated statistics
     */
    public function getCrossEntityStats(
        string $tenantId,
        array $entityIds,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
    ): array {
        $entityStats = [];
        $consolidatedTotal = Money::of(0, 'USD');
        $consolidatedCount = 0;
        $consolidatedByMethod = [];

        foreach ($entityIds as $entityId) {
            $stats = $this->paymentService->getEntityPaymentStats(
                $entityId,
                $periodStart,
                $periodEnd,
            );

            $entityStats[$entityId] = $stats;
            $consolidatedTotal = $consolidatedTotal->add($stats['total_paid']);
            $consolidatedCount += $stats['payment_count'];

            foreach ($stats['by_method'] as $method => $methodStats) {
                if (!isset($consolidatedByMethod[$method])) {
                    $consolidatedByMethod[$method] = [
                        'count' => 0,
                        'amount' => Money::of(0, 'USD'),
                    ];
                }
                $consolidatedByMethod[$method]['count'] += $methodStats['count'];
                $consolidatedByMethod[$method]['amount'] = $consolidatedByMethod[$method]['amount']->add($methodStats['amount']);
            }
        }

        return [
            'period' => [
                'start' => $periodStart,
                'end' => $periodEnd,
            ],
            'consolidated' => [
                'total_paid' => $consolidatedTotal,
                'payment_count' => $consolidatedCount,
                'entity_count' => count($entityIds),
                'by_method' => $consolidatedByMethod,
            ],
            'by_entity' => $entityStats,
            'generated_at' => new \DateTimeImmutable(),
        ];
    }

    /**
     * Validate vendor can be paid by entity.
     *
     * @param string $entityId Legal entity
     * @param string $vendorId Vendor to pay
     * @return array{valid: bool, reason: ?string}
     */
    public function validateVendorPayment(string $entityId, string $vendorId): array
    {
        return $this->paymentService->validatePaymentPermission($entityId, $vendorId);
    }

    /**
     * Get available banks for entity payment.
     *
     * @param string $entityId Legal entity
     * @param string $currency Payment currency
     * @return array Available bank accounts
     */
    public function getAvailableBanks(string $entityId, string $currency): array
    {
        return $this->paymentService->getEntityPaymentBanks($entityId, $currency);
    }

    /**
     * Validate individual payment item.
     */
    private function validatePaymentItem(
        string $entityId,
        PaymentItemData $item,
        string $paymentMethod,
    ): array {
        // Check vendor payment permission
        $vendorValidation = $this->paymentService->validatePaymentPermission(
            $entityId,
            $item->vendorId,
        );

        if (!$vendorValidation['valid']) {
            return $vendorValidation;
        }

        // Validate bank details for electronic payments
        if (in_array($paymentMethod, ['ACH', 'WIRE'], true)) {
            if (!$item->hasCompleteBankDetails()) {
                return [
                    'valid' => false,
                    'reason' => 'Incomplete bank details for electronic payment',
                ];
            }
        }

        // Validate amount is positive
        if ($item->amount->isZero() || $item->amount->getAmount() < 0) {
            return [
                'valid' => false,
                'reason' => 'Invalid payment amount',
            ];
        }

        return ['valid' => true, 'reason' => null];
    }

    /**
     * Calculate total amount from payment items.
     *
     * @param array<PaymentItemData> $items
     */
    private function calculateTotalAmount(array $items): Money
    {
        if (empty($items)) {
            return Money::of(0, 'USD');
        }

        $total = Money::of(0, $items[0]->amount->getCurrency());
        foreach ($items as $item) {
            $total = $total->add($item->amount);
        }

        return $total;
    }

    /**
     * Generate unique batch ID.
     */
    private function generateBatchId(): string
    {
        if ($this->idGenerator !== null) {
            return 'MEPB-' . strtoupper($this->idGenerator->randomHex(8));
        }

        return 'MEPB-' . strtoupper(bin2hex(random_bytes(8)));
    }
}
