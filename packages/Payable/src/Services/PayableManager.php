<?php

declare(strict_types=1);

namespace Nexus\Payable\Services;

use Nexus\Payable\Contracts\PayableManagerInterface;
use Nexus\Payable\Contracts\VendorRepositoryInterface;
use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
use Nexus\Payable\Contracts\ThreeWayMatcherInterface;
use Nexus\Payable\Contracts\PaymentSchedulerInterface;
use Nexus\Payable\Contracts\PaymentAllocationInterface;
use Nexus\Payable\Contracts\VendorInterface;
use Nexus\Payable\Contracts\VendorBillInterface;
use Nexus\Payable\Contracts\PaymentInterface;
use Nexus\Payable\Contracts\MatchingResultInterface;
use Nexus\Payable\Enums\BillStatus;
use Nexus\Payable\Exceptions\BillNotFoundException;
use Nexus\Payable\Exceptions\InvalidBillStateException;
use Nexus\Finance\Contracts\FinanceManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

/**
 * Main payable manager orchestrator.
 */
final class PayableManager implements PayableManagerInterface
{
    public function __construct(
        private readonly VendorManager $vendorManager,
        private readonly BillProcessor $billProcessor,
        private readonly MatchingEngine $matchingEngine,
        private readonly PaymentScheduler $paymentScheduler,
        private readonly PaymentProcessor $paymentProcessor,
        private readonly VendorRepositoryInterface $vendorRepository,
        private readonly VendorBillRepositoryInterface $billRepository,
        private readonly FinanceManagerInterface $financeManager,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function createVendor(string $tenantId, array $data): VendorInterface
    {
        return $this->vendorManager->createVendor($tenantId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function updateVendor(string $vendorId, array $data): VendorInterface
    {
        return $this->vendorManager->updateVendor($vendorId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getVendor(string $vendorId): VendorInterface
    {
        return $this->vendorManager->getVendor($vendorId);
    }

    /**
     * {@inheritdoc}
     */
    public function listVendors(string $tenantId, array $filters = []): array
    {
        return $this->vendorManager->listVendors($tenantId, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function submitBill(string $tenantId, array $billData): VendorBillInterface
    {
        return $this->billProcessor->submitBill($tenantId, $billData);
    }

    /**
     * {@inheritdoc}
     */
    public function matchBill(string $billId): MatchingResultInterface
    {
        $bill = $this->billProcessor->matchBill($billId);
        
        // Get vendor for matching
        $vendor = $this->vendorRepository->findById($bill->getVendorId());
        
        // Return full matching result
        return $this->matchingEngine->match($bill, $vendor);
    }

    /**
     * {@inheritdoc}
     */
    public function approveBill(string $billId, string $approvedBy): VendorBillInterface
    {
        return $this->billProcessor->approveBill($billId, $approvedBy);
    }

    /**
     * {@inheritdoc}
     */
    public function postBillToGL(string $billId): string
    {
        $bill = $this->billRepository->findById($billId);
        if (!$bill) {
            throw BillNotFoundException::forId($billId);
        }

        // Validate bill is approved
        if ($bill->getStatus() !== BillStatus::APPROVED->value) {
            throw InvalidBillStateException::cannotPost($billId, $bill->getStatus());
        }

        // Build GL journal lines
        $lines = [];

        // Debit expense accounts from bill lines
        foreach ($bill->getLines() as $line) {
            $lines[] = [
                'account' => $line->getGlAccount(),
                'debit' => $line->getLineAmount(),
                'credit' => 0.0,
                'description' => $line->getDescription(),
            ];
        }

        // Credit AP control account
        $lines[] = [
            'account' => '2100', // AP Control (should be configurable)
            'debit' => 0.0,
            'credit' => $bill->getTotalAmount(),
            'description' => "Vendor bill {$bill->getBillNumber()}",
        ];

        // Post journal via Finance module
        $journalId = $this->financeManager->postJournal(
            tenantId: $bill->getTenantId(),
            journalDate: $bill->getBillDate(),
            description: "Vendor bill {$bill->getBillNumber()}",
            lines: $lines
        );

        // Update bill with GL journal ID
        $this->billRepository->update($billId, [
            'gl_journal_id' => $journalId,
            'status' => BillStatus::POSTED->value,
        ]);

        $this->auditLogger->log(
            entity: 'vendor_bill',
            entityId: $billId,
            action: 'posted_to_gl',
            tenantId: $bill->getTenantId(),
            metadata: ['gl_journal_id' => $journalId]
        );

        $this->logger->info("Bill posted to GL: {$billId} - Journal {$journalId}");

        return $journalId;
    }

    /**
     * {@inheritdoc}
     */
    public function schedulePayment(string $billId): string
    {
        $schedule = $this->paymentScheduler->schedule($billId);
        return $schedule->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function processPayment(string $tenantId, array $paymentData): PaymentInterface
    {
        return $this->paymentProcessor->processPayment($tenantId, $paymentData);
    }

    /**
     * {@inheritdoc}
     */
    public function allocatePayment(string $paymentId, array $allocations): PaymentInterface
    {
        return $this->paymentProcessor->allocatePayment($paymentId, $allocations);
    }

    /**
     * {@inheritdoc}
     */
    public function voidPayment(string $paymentId, string $reason): void
    {
        $this->paymentProcessor->voidPayment($paymentId, $reason);
    }

    /**
     * {@inheritdoc}
     */
    public function getBill(string $billId): VendorBillInterface
    {
        return $this->billProcessor->getBill($billId);
    }

    /**
     * {@inheritdoc}
     */
    public function getVendorBills(string $tenantId, string $vendorId): array
    {
        return $this->billProcessor->listVendorBills($tenantId, $vendorId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentsDue(string $tenantId, \DateTimeInterface $asOfDate): array
    {
        return $this->paymentScheduler->getPaymentsDue($tenantId, $asOfDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getVendorAging(string $tenantId, string $vendorId, \DateTimeInterface $asOfDate): array
    {
        $bills = $this->billRepository->getByVendor($tenantId, $vendorId);
        
        $aging = [
            'current' => 0.0,
            '1_30_days' => 0.0,
            '31_60_days' => 0.0,
            '61_90_days' => 0.0,
            'over_90_days' => 0.0,
            'total' => 0.0,
        ];

        foreach ($bills as $bill) {
            if (!in_array($bill->getStatus(), [BillStatus::POSTED->value, BillStatus::PARTIALLY_PAID->value])) {
                continue; // Skip non-posted bills
            }

            $daysOverdue = $asOfDate->diff($bill->getDueDate())->days;
            $amount = $bill->getTotalAmount();

            if ($daysOverdue <= 0) {
                $aging['current'] += $amount;
            } elseif ($daysOverdue <= 30) {
                $aging['1_30_days'] += $amount;
            } elseif ($daysOverdue <= 60) {
                $aging['31_60_days'] += $amount;
            } elseif ($daysOverdue <= 90) {
                $aging['61_90_days'] += $amount;
            } else {
                $aging['over_90_days'] += $amount;
            }

            $aging['total'] += $amount;
        }

        return $aging;
    }
}
