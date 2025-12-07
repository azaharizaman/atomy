<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\ProcurementOperations\DTOs\PaymentBatchContext;
use Nexus\ProcurementOperations\DTOs\ProcessPaymentRequest;
use Nexus\ProcurementOperations\DataProviders\PaymentDataProvider;
use Nexus\ProcurementOperations\Exceptions\PaymentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for building and optimizing payment batches.
 *
 * Handles the logic of grouping invoices into optimal payment batches,
 * calculating totals, and preparing payment context.
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * - Service handles complex calculation logic
 * - Coordinator delegates batch building to this service
 */
final class PaymentBatchBuilder
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly PaymentDataProvider $dataProvider,
        private readonly PaymentIdGenerator $idGenerator,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Build a payment batch context from a payment request.
     *
     * @throws PaymentException If batch cannot be built
     */
    public function buildBatch(ProcessPaymentRequest $request): PaymentBatchContext
    {
        $this->logger->info('Building payment batch', [
            'tenantId' => $request->tenantId,
            'invoiceCount' => count($request->vendorBillIds),
            'paymentMethod' => $request->paymentMethod,
        ]);

        // Generate a unique batch ID
        $paymentBatchId = $this->idGenerator->generateBatchId();

        // Use data provider to build the context
        $context = $this->dataProvider->buildBatchContext(
            tenantId: $request->tenantId,
            paymentBatchId: $paymentBatchId,
            vendorBillIds: $request->vendorBillIds,
            paymentMethod: $request->paymentMethod,
            bankAccountId: $request->bankAccountId,
            calculateDiscounts: $request->takeEarlyPaymentDiscount,
        );

        $this->logger->info('Payment batch built successfully', [
            'batchId' => $context->paymentBatchId,
            'totalAmountCents' => $context->totalAmountCents,
            'netAmountCents' => $context->netAmountCents,
            'discountCents' => $context->totalDiscountCents,
        ]);

        return $context;
    }

    /**
     * Build optimized batches grouped by payment method.
     *
     * Splits invoices into separate batches based on optimal payment methods
     * (e.g., wire for international, ACH for domestic).
     *
     * @param string $tenantId
     * @param array<string> $vendorBillIds
     * @param string $bankAccountId
     * @param string $processedBy User ID processing the payment
     * @return array<PaymentBatchContext>
     */
    public function buildOptimizedBatches(
        string $tenantId,
        array $vendorBillIds,
        string $bankAccountId,
        string $processedBy,
    ): array {
        $batches = [];
        $groupedByMethod = $this->groupByOptimalPaymentMethod($tenantId, $vendorBillIds);

        foreach ($groupedByMethod as $paymentMethod => $billIds) {
            $request = new ProcessPaymentRequest(
                tenantId: $tenantId,
                vendorBillIds: $billIds,
                paymentMethod: $paymentMethod,
                bankAccountId: $bankAccountId,
                processedBy: $processedBy,
            );

            $batches[] = $this->buildBatch($request);
        }

        return $batches;
    }

    /**
     * Build batches grouped by vendor.
     *
     * Creates separate batches per vendor for clearer vendor statements.
     *
     * @param string $tenantId
     * @param array<string> $vendorBillIds
     * @param string $paymentMethod
     * @param string $bankAccountId
     * @param string $processedBy User ID processing the payment
     * @return array<PaymentBatchContext>
     */
    public function buildVendorBatches(
        string $tenantId,
        array $vendorBillIds,
        string $paymentMethod,
        string $bankAccountId,
        string $processedBy,
    ): array {
        $batches = [];
        $groupedByVendor = $this->groupByVendor($tenantId, $vendorBillIds);

        foreach ($groupedByVendor as $vendorId => $billIds) {
            $request = new ProcessPaymentRequest(
                tenantId: $tenantId,
                vendorBillIds: $billIds,
                paymentMethod: $paymentMethod,
                bankAccountId: $bankAccountId,
                processedBy: $processedBy,
            );

            $batches[] = $this->buildBatch($request);
        }

        return $batches;
    }

    /**
     * Calculate optimal payment date for early payment discounts.
     *
     * Analyzes discount terms and determines the best payment date
     * to maximize savings while managing cash flow.
     *
     * @return \DateTimeImmutable Optimal payment date
     */
    public function calculateOptimalPaymentDate(PaymentBatchContext $context): \DateTimeImmutable
    {
        $today = new \DateTimeImmutable('today');
        $discountEligible = $context->getDiscountEligibleInvoices();

        if (empty($discountEligible)) {
            // No discounts available - use latest due date
            $latestDueDate = $today;
            foreach ($context->invoices as $invoice) {
                $dueDate = $invoice['dueDate'] ?? $today;
                if ($dueDate > $latestDueDate) {
                    $latestDueDate = $dueDate;
                }
            }
            return $latestDueDate;
        }

        // Find the earliest discount deadline
        $earliestDiscountDate = null;
        foreach ($discountEligible as $invoice) {
            $discountDate = $invoice['discountDate'] ?? null;
            if ($discountDate !== null) {
                if ($earliestDiscountDate === null || $discountDate < $earliestDiscountDate) {
                    $earliestDiscountDate = $discountDate;
                }
            }
        }

        // Return earliest discount date or today if no discount dates
        return $earliestDiscountDate ?? $today;
    }

    /**
     * Calculate potential savings if paid by discount date.
     *
     * @return array{
     *     totalSavingsCents: int,
     *     discountEligibleCount: int,
     *     discountEligibleAmountCents: int,
     *     recommendedPaymentDate: \DateTimeImmutable
     * }
     */
    public function calculatePotentialSavings(PaymentBatchContext $context): array
    {
        $totalSavings = 0;
        $eligibleCount = 0;
        $eligibleAmount = 0;
        $today = new \DateTimeImmutable('today');

        foreach ($context->invoices as $invoice) {
            $discountDate = $invoice['discountDate'] ?? null;
            $discountCents = $invoice['discountCents'] ?? 0;

            if ($discountCents > 0 && $discountDate !== null && $discountDate >= $today) {
                $totalSavings += $discountCents;
                $eligibleCount++;
                $eligibleAmount += $invoice['amountCents'] ?? 0;
            }
        }

        return [
            'totalSavingsCents' => $totalSavings,
            'discountEligibleCount' => $eligibleCount,
            'discountEligibleAmountCents' => $eligibleAmount,
            'recommendedPaymentDate' => $this->calculateOptimalPaymentDate($context),
        ];
    }

    /**
     * Group vendor bills by optimal payment method.
     *
     * @param string $tenantId
     * @param array<string> $vendorBillIds
     * @return array<string, array<string>>
     */
    private function groupByOptimalPaymentMethod(string $tenantId, array $vendorBillIds): array
    {
        // Query vendor bills and determine optimal payment method for each vendor
        $bills = $this->dataProvider->getBillsByIds($tenantId, $vendorBillIds);
        $grouped = [];
        
        foreach ($bills as $bill) {
            $billId = $bill['id'] ?? null;
            if (!$billId) {
                continue;
            }
            
            // Determine optimal payment method based on vendor preferences and location
            // For international vendors, use 'wire'; for domestic, use 'ach' or 'check'
            $vendorCountry = $bill['vendorCountry'] ?? null;
            $tenantCountry = $bill['tenantCountry'] ?? null;
            $isInternational = ($vendorCountry !== null && $tenantCountry !== null && $vendorCountry !== $tenantCountry);
            $paymentMethod = $isInternational ? 'wire' : ($bill['vendorPreferredMethod'] ?? 'ach');
            
            $grouped[$paymentMethod][] = $billId;
        }
        
        return $grouped;
    }

    /**
     * Group vendor bills by vendor.
     *
     * @param string $tenantId
     * @param array<string> $vendorBillIds
     * @return array<string, array<string>>
     */
    private function groupByVendor(string $tenantId, array $vendorBillIds): array
    {
        // Query vendor bills and group by vendor ID
        $bills = $this->dataProvider->getBillsByIds($tenantId, $vendorBillIds);
        $grouped = [];
        
        foreach ($bills as $bill) {
            $vendorId = $bill['vendorId'] ?? null;
            $billId = $bill['id'] ?? null;
            if ($vendorId && $billId) {
                $grouped[$vendorId][] = $billId;
            }
        }
        
        return $grouped;
    }
}
