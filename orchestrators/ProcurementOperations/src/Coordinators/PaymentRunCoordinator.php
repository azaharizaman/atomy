<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\PaymentBatchBuilderInterface;
use Nexus\ProcurementOperations\Contracts\PaymentRunCoordinatorInterface;
use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ProcurementOperations\DTOs\PaymentRunRequest;
use Nexus\ProcurementOperations\DTOs\PaymentRunResult;
use Nexus\ProcurementOperations\DTOs\ProcessPaymentRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates batch payment runs.
 */
final readonly class PaymentRunCoordinator implements PaymentRunCoordinatorInterface
{
    public function __construct(
        private PaymentBatchBuilderInterface $batchBuilder,
        private LoggerInterface $logger = new NullLogger(),
        private ?SecureIdGeneratorInterface $secureIdGenerator = null,
    ) {}

    /**
     * @inheritDoc
     */
    public function createRun(PaymentRunRequest $request): PaymentRunResult
    {
        if ($this->isBlank($request->tenantId)) {
            return PaymentRunResult::failure('Tenant ID is required to create a payment run.');
        }

        $vendorBillIds = $this->extractVendorBillIds($request->filters);
        if ($vendorBillIds === []) {
            return PaymentRunResult::failure(
                'At least one vendor bill ID is required in filters.vendorBillIds.'
            );
        }

        $this->logger->info('Creating payment run', [
            'tenant_id' => $request->tenantId,
            'bank_account_id' => $request->bankAccountId,
            'vendor_bill_count' => count($vendorBillIds),
        ]);

        try {
            $batch = $this->batchBuilder->buildBatch(new ProcessPaymentRequest(
                tenantId: $request->tenantId,
                vendorBillIds: $vendorBillIds,
                paymentMethod: $request->paymentMethod ?? 'bank_transfer',
                bankAccountId: $request->bankAccountId,
                processedBy: $request->initiatedBy,
                scheduledDate: $request->paymentDate,
                takeEarlyPaymentDiscount: (bool) ($request->filters['takeEarlyPaymentDiscount'] ?? true),
                metadata: $request->filters,
            ));

            return PaymentRunResult::success(
                paymentRunId: $this->generatePaymentRunId(),
                totalPayments: $batch->getInvoiceCount(),
                totalAmountCents: $batch->totalAmountCents,
                status: 'draft',
                message: 'Draft payment run created with ' . $batch->getInvoiceCount() . ' items.'
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create payment run', [
                'error' => $e->getMessage(),
            ]);
            return PaymentRunResult::failure($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function approveRun(string $tenantId, string $paymentRunId, string $approvedBy): PaymentRunResult
    {
        if ($this->isBlank($tenantId) || $this->isBlank($paymentRunId) || $this->isBlank($approvedBy)) {
            return PaymentRunResult::failure('Tenant ID, payment run ID, and approver are required.');
        }

        $this->logger->info('Approving payment run', [
            'tenant_id' => $tenantId,
            'payment_run_id' => $paymentRunId,
            'approved_by' => $approvedBy,
        ]);

        return PaymentRunResult::success(
            paymentRunId: $paymentRunId,
            totalPayments: 0,
            totalAmountCents: 0,
            status: 'approved',
            message: sprintf('Payment run %s approved by %s.', $paymentRunId, $approvedBy),
        );
    }

    /**
     * @inheritDoc
     */
    public function executeRun(string $tenantId, string $paymentRunId, string $executedBy): PaymentRunResult
    {
        if ($this->isBlank($tenantId) || $this->isBlank($paymentRunId) || $this->isBlank($executedBy)) {
            return PaymentRunResult::failure('Tenant ID, payment run ID, and executor are required.');
        }

        $this->logger->info('Executing payment run', [
            'tenant_id' => $tenantId,
            'payment_run_id' => $paymentRunId,
            'executed_by' => $executedBy,
        ]);

        return PaymentRunResult::success(
            paymentRunId: $paymentRunId,
            totalPayments: 0,
            totalAmountCents: 0,
            status: 'executed',
            message: sprintf('Payment run %s executed by %s.', $paymentRunId, $executedBy),
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string>
     */
    private function extractVendorBillIds(array $filters): array
    {
        $rawVendorBillIds = $filters['vendorBillIds'] ?? [];
        if (!is_array($rawVendorBillIds)) {
            return [];
        }

        $vendorBillIds = [];
        foreach ($rawVendorBillIds as $rawVendorBillId) {
            if (!is_string($rawVendorBillId)) {
                continue;
            }

            $vendorBillId = trim($rawVendorBillId);
            if ($vendorBillId === '') {
                continue;
            }

            $vendorBillIds[] = $vendorBillId;
        }

        return array_values(array_unique($vendorBillIds));
    }

    private function generatePaymentRunId(): string
    {
        try {
            $randomHex = $this->secureIdGenerator?->randomHex(8) ?? bin2hex(random_bytes(8));
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Unable to generate payment run ID.', 0, $exception);
        }

        return 'prun-' . strtolower(substr($randomHex, 0, 16));
    }

    private function isBlank(string $value): bool
    {
        return trim($value) === '';
    }
}
