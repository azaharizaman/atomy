<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows\Steps;

use Nexus\ProcurementOperations\Contracts\AccrualServiceInterface;
use Nexus\ProcurementOperations\Contracts\SagaStepInterface;
use Nexus\ProcurementOperations\DTOs\SagaStepContext;
use Nexus\ProcurementOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Create accrual journal entry.
 *
 * Forward action: Creates accrual entry when GR is posted but invoice not received.
 * Compensation: Reverses the accrual entry.
 */
final readonly class CreateAccrualStep implements SagaStepInterface
{
    public function __construct(
        private AccrualServiceInterface $accrualService,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getId(): string
    {
        return 'create_accrual';
    }

    public function getName(): string
    {
        return 'Create Accrual';
    }

    public function getDescription(): string
    {
        return 'Creates accrual journal entry for goods received but not yet invoiced';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->logger->info('Creating accrual entry', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $grId = $context->getStepOutput('receive_goods', 'goods_receipt_id')
                ?? $context->get('goods_receipt_id');

            $poId = $context->getStepOutput('create_purchase_order', 'purchase_order_id')
                ?? $context->get('purchase_order_id');

            // Check if accrual is needed (GR exists but invoice not matched)
            $matchData = $context->getStepOutput('three_way_match');
            $hasInvoice = $context->has('vendor_invoice_id');

            if ($hasInvoice && $matchData !== null && ($matchData['match_status'] ?? '') === 'matched') {
                // Invoice already matched, no accrual needed
                return SagaStepResult::success([
                    'accrual_created' => false,
                    'reason' => 'Invoice already matched, no accrual needed',
                ]);
            }

            $accrualResult = $this->accrualService->createAccrual(
                tenantId: $context->tenantId,
                goodsReceiptId: $grId,
                purchaseOrderId: $poId,
                createdBy: $context->userId,
            );

            return SagaStepResult::success([
                'accrual_created' => true,
                'journal_entry_id' => $accrualResult->journalEntryId,
                'accrual_amount' => $accrualResult->amount,
                'accrual_date' => $accrualResult->accrualDate,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create accrual entry', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Failed to create accrual entry: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->logger->info('Compensating: Reversing accrual entry', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $stepOutput = $context->getStepOutput('create_accrual');

            if ($stepOutput === null || !($stepOutput['accrual_created'] ?? false)) {
                return SagaStepResult::compensated([
                    'message' => 'No accrual to reverse',
                ]);
            }

            $journalEntryId = $stepOutput['journal_entry_id'] ?? null;

            if ($journalEntryId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No journal entry to reverse',
                ]);
            }

            $this->accrualService->reverseAccrual(
                journalEntryId: $journalEntryId,
                reason: 'Saga compensation - process rolled back',
                reversedBy: $context->userId,
            );

            return SagaStepResult::compensated([
                'reversed_journal_entry_id' => $journalEntryId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to reverse accrual during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to reverse accrual: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 8;
    }

    public function isRequired(): bool
    {
        return false; // Accrual is optional based on business logic
    }

    public function getTimeout(): int
    {
        return 300; // 5 minutes
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }
}
