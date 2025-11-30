<?php

declare(strict_types=1);

namespace Nexus\Payable\Services;

use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
use Nexus\Payable\Contracts\VendorRepositoryInterface;
use Nexus\Payable\Contracts\ThreeWayMatcherInterface;
use Nexus\Payable\Contracts\VendorBillInterface;
use Nexus\Payable\Enums\BillStatus;
use Nexus\Payable\Enums\MatchingStatus;
use Nexus\Payable\Exceptions\BillNotFoundException;
use Nexus\Payable\Exceptions\DuplicateBillException;
use Nexus\Payable\Exceptions\InvalidBillStateException;
use Nexus\Payable\Exceptions\VendorNotFoundException;
use Nexus\Payable\ValueObjects\VendorBillNumber;
use Nexus\AuditLogger\Contracts\AuditLoggerInterface;
use Nexus\Currency\Contracts\RateProviderInterface;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Bill processing service.
 */
final class BillProcessor
{
    public function __construct(
        private readonly VendorBillRepositoryInterface $billRepository,
        private readonly VendorRepositoryInterface $vendorRepository,
        private readonly ThreeWayMatcherInterface $matcher,
        private readonly RateProviderInterface $currencyRateProvider,
        private readonly PeriodManagerInterface $periodManager,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Submit a new vendor bill.
     *
     * @param string $tenantId
     * @param array $billData Bill data including lines
     * @return VendorBillInterface
     * @throws VendorNotFoundException
     * @throws DuplicateBillException
     */
    public function submitBill(string $tenantId, array $billData): VendorBillInterface
    {
        $billNumber = new VendorBillNumber($billData['bill_number']);
        $vendorId = $billData['vendor_id'];

        // Validate vendor exists
        $vendor = $this->vendorRepository->findById($vendorId);
        if (!$vendor) {
            throw VendorNotFoundException::forId($vendorId);
        }

        // Check for duplicate bill number
        if ($this->billRepository->findByBillNumber($tenantId, $vendorId, (string)$billNumber)) {
            throw DuplicateBillException::forBillNumber((string)$billNumber, $vendorId);
        }

        // Validate period is open
        $billDate = new \DateTime($billData['bill_date']);
        $period = $this->periodManager->getPeriodByDate($tenantId, $billDate);
        if (!$period->isOpen()) {
            throw new \RuntimeException("Cannot submit bill: Period {$period->getName()} is not open");
        }

        // Get currency exchange rate
        $currency = $billData['currency'] ?? $vendor->getCurrency();
        $exchangeRate = 1.0;
        if ($currency !== 'MYR') { // Assuming MYR is base currency
            $exchangeRate = $this->currencyRateProvider->getRate($currency, 'MYR', $billDate);
        }

        // Calculate totals
        $subtotal = 0.0;
        foreach ($billData['lines'] as $line) {
            $subtotal += $line['quantity'] * $line['unit_price'];
        }
        $taxAmount = $billData['tax_amount'] ?? 0.0;
        $totalAmount = $subtotal + $taxAmount;

        // Create bill
        $bill = $this->billRepository->create($tenantId, [
            'vendor_id' => $vendorId,
            'bill_number' => (string)$billNumber,
            'bill_date' => $billData['bill_date'],
            'due_date' => $billData['due_date'],
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'status' => BillStatus::DRAFT->value,
            'matching_status' => MatchingStatus::PENDING->value,
            'description' => $billData['description'] ?? null,
            'lines' => $billData['lines'],
        ]);

        $this->auditLogger->log(
            entity: 'vendor_bill',
            entityId: $bill->getId(),
            action: 'submitted',
            tenantId: $tenantId,
            changes: $billData
        );

        $this->logger->info("Bill submitted: {$bill->getBillNumber()} ({$bill->getId()})");

        return $bill;
    }

    /**
     * Match bill against PO and GRN.
     *
     * @param string $billId
     * @return VendorBillInterface
     * @throws BillNotFoundException
     * @throws InvalidBillStateException
     */
    public function matchBill(string $billId): VendorBillInterface
    {
        $bill = $this->billRepository->findById($billId);
        if (!$bill) {
            throw BillNotFoundException::forId($billId);
        }

        // Validate bill can be matched
        if (!in_array($bill->getStatus(), [BillStatus::DRAFT->value, BillStatus::PENDING_MATCHING->value])) {
            throw InvalidBillStateException::cannotMatch($billId, $bill->getStatus());
        }

        $vendor = $this->vendorRepository->findById($bill->getVendorId());
        if (!$vendor) {
            throw VendorNotFoundException::forId($bill->getVendorId());
        }

        // Perform 3-way matching
        $matchingResult = $this->matcher->match($bill, $vendor);

        // Update bill with matching results
        $newStatus = match($matchingResult->getStatus()) {
            MatchingStatus::MATCHED->value => BillStatus::MATCHED->value,
            MatchingStatus::VARIANCE_REVIEW->value => BillStatus::VARIANCE_REVIEW->value,
            default => BillStatus::PENDING_MATCHING->value,
        };

        $updatedBill = $this->billRepository->update($billId, [
            'status' => $newStatus,
            'matching_status' => $matchingResult->getStatus(),
        ]);

        $this->auditLogger->log(
            entity: 'vendor_bill',
            entityId: $billId,
            action: 'matched',
            tenantId: $bill->getTenantId(),
            metadata: [
                'matching_status' => $matchingResult->getStatus(),
                'variances' => $matchingResult->getVariances(),
            ]
        );

        $this->logger->info("Bill matched: {$billId} - {$matchingResult->getStatus()}");

        return $updatedBill;
    }

    /**
     * Approve bill for payment.
     *
     * @param string $billId
     * @param string $approvedBy
     * @return VendorBillInterface
     */
    public function approveBill(string $billId, string $approvedBy): VendorBillInterface
    {
        $bill = $this->billRepository->findById($billId);
        if (!$bill) {
            throw BillNotFoundException::forId($billId);
        }

        $updatedBill = $this->billRepository->update($billId, [
            'status' => BillStatus::APPROVED->value,
        ]);

        $this->auditLogger->log(
            entity: 'vendor_bill',
            entityId: $billId,
            action: 'approved',
            tenantId: $bill->getTenantId(),
            metadata: ['approved_by' => $approvedBy]
        );

        $this->logger->info("Bill approved: {$billId} by {$approvedBy}");

        return $updatedBill;
    }

    /**
     * Get bill by ID.
     *
     * @param string $billId
     * @return VendorBillInterface
     * @throws BillNotFoundException
     */
    public function getBill(string $billId): VendorBillInterface
    {
        $bill = $this->billRepository->findById($billId);
        if (!$bill) {
            throw BillNotFoundException::forId($billId);
        }

        return $bill;
    }

    /**
     * List bills for vendor.
     *
     * @param string $tenantId
     * @param string $vendorId
     * @param array $filters
     * @return array<VendorBillInterface>
     */
    public function listVendorBills(string $tenantId, string $vendorId, array $filters = []): array
    {
        return $this->billRepository->getByVendor($tenantId, $vendorId, $filters);
    }
}
