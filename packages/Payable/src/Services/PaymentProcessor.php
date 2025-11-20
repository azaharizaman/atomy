<?php

declare(strict_types=1);

namespace Nexus\Payable\Services;

use Nexus\Payable\Contracts\PaymentAllocationInterface;
use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
use Nexus\Payable\Contracts\PaymentInterface;
use Nexus\Payable\Contracts\PaymentRepositoryInterface;
use Nexus\Payable\Enums\BillStatus;
use Nexus\Payable\Enums\PaymentStatus;
use Nexus\Payable\Exceptions\InvalidBillStateException;
use Nexus\Payable\Exceptions\PaymentProcessingException;
use Nexus\Finance\Contracts\FinanceManagerInterface;
use Nexus\Currency\Contracts\RateProviderInterface;
use Nexus\AuditLogger\Contracts\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

/**
 * Payment processing and allocation service.
 */
final class PaymentProcessor implements PaymentAllocationInterface
{
    public function __construct(
        private readonly VendorBillRepositoryInterface $billRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly FinanceManagerInterface $financeManager,
        private readonly RateProviderInterface $currencyRateProvider,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function processPayment(string $tenantId, array $paymentData): PaymentInterface
    {
        $paymentDate = new \DateTime($paymentData['payment_date']);
        $currency = $paymentData['currency'];

        // Get exchange rate
        $exchangeRate = 1.0;
        if ($currency !== 'MYR') {
            $exchangeRate = $this->currencyRateProvider->getRate($currency, 'MYR', $paymentDate);
        }

        // Create payment
        $payment = $this->paymentRepository->create($tenantId, [
            'payment_number' => $this->generatePaymentNumber($tenantId),
            'payment_date' => $paymentData['payment_date'],
            'amount' => $paymentData['amount'],
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'payment_method' => $paymentData['payment_method'],
            'bank_account' => $paymentData['bank_account'],
            'reference' => $paymentData['reference'] ?? '',
            'status' => PaymentStatus::PROCESSING->value,
            'allocations' => [],
        ]);

        $this->logger->info("Payment created: {$payment->getPaymentNumber()} ({$payment->getId()})");

        // Allocate to bills
        if (!empty($paymentData['allocations'])) {
            $payment = $this->allocatePayment($payment->getId(), $paymentData['allocations']);
        }

        // Post to GL
        try {
            $glJournalId = $this->postPaymentToGL($payment);
            $payment = $this->paymentRepository->update($payment->getId(), [
                'gl_journal_id' => $glJournalId,
                'status' => PaymentStatus::PAID->value,
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Payment GL posting failed: {$e->getMessage()}");
            throw PaymentProcessingException::glPostingFailed($payment->getId(), $e->getMessage());
        }

        $this->auditLogger->log(
            entity: 'payment',
            entityId: $payment->getId(),
            action: 'processed',
            tenantId: $tenantId,
            changes: $paymentData
        );

        return $payment;
    }

    /**
     * {@inheritdoc}
     */
    public function allocatePayment(string $paymentId, array $allocations): PaymentInterface
    {
        $payment = $this->paymentRepository->findById($paymentId);
        if (!$payment) {
            throw new \RuntimeException("Payment '{$paymentId}' not found");
        }

        $totalAllocated = 0.0;
        $updatedAllocations = [];

        foreach ($allocations as $allocation) {
            $billId = $allocation['bill_id'];
            $amount = $allocation['amount'];

            // Validate bill exists and is posted
            $bill = $this->billRepository->findById($billId);
            if (!$bill) {
                throw PaymentProcessingException::invalidAllocation($paymentId, "Bill '{$billId}' not found");
            }

            if ($bill->getStatus() !== BillStatus::POSTED->value) {
                throw InvalidBillStateException::cannotPay($billId, $bill->getStatus());
            }

            $totalAllocated += $amount;
            $updatedAllocations[] = [
                'bill_id' => $billId,
                'amount' => $amount,
            ];

            // Update bill status
            $this->billRepository->update($billId, [
                'status' => BillStatus::PARTIALLY_PAID->value,
            ]);
        }

        // Validate total allocation
        if ($totalAllocated > $payment->getAmount()) {
            throw PaymentProcessingException::invalidAllocation($paymentId, "Total allocation ({$totalAllocated}) exceeds payment amount ({$payment->getAmount()})");
        }

        // Update payment with allocations
        $updatedPayment = $this->paymentRepository->update($paymentId, [
            'allocations' => $updatedAllocations,
        ]);

        $this->logger->info("Payment allocated: {$paymentId} - {$totalAllocated} to " . count($allocations) . " bills");

        return $updatedPayment;
    }

    /**
     * {@inheritdoc}
     */
    public function reconcilePayment(string $paymentId, array $bankReconciliation): void
    {
        $payment = $this->paymentRepository->findById($paymentId);
        if (!$payment) {
            throw new \RuntimeException("Payment '{$paymentId}' not found");
        }

        $this->paymentRepository->update($paymentId, [
            'status' => PaymentStatus::RECONCILED->value,
        ]);

        $this->auditLogger->log(
            entity: 'payment',
            entityId: $paymentId,
            action: 'reconciled',
            tenantId: $payment->getTenantId(),
            metadata: $bankReconciliation
        );

        $this->logger->info("Payment reconciled: {$paymentId}");
    }

    /**
     * {@inheritdoc}
     */
    public function calculatePaymentAmount(string $billId, ?\DateTimeInterface $paymentDate = null): float
    {
        $bill = $this->billRepository->findById($billId);
        if (!$bill) {
            throw new \RuntimeException("Bill '{$billId}' not found");
        }

        // For now, return total amount (future: deduct early payment discounts)
        return $bill->getTotalAmount();
    }

    /**
     * {@inheritdoc}
     */
    public function voidPayment(string $paymentId, string $reason): void
    {
        $payment = $this->paymentRepository->findById($paymentId);
        if (!$payment) {
            throw new \RuntimeException("Payment '{$paymentId}' not found");
        }

        // Reverse GL journal
        if ($payment->getGlJournalId()) {
            $this->financeManager->reverseJournal($payment->getGlJournalId(), $reason);
        }

        // Update payment status
        $this->paymentRepository->update($paymentId, [
            'status' => PaymentStatus::VOIDED->value,
        ]);

        // Revert bill statuses
        foreach ($payment->getAllocations() as $allocation) {
            $this->billRepository->update($allocation['bill_id'], [
                'status' => BillStatus::POSTED->value,
            ]);
        }

        $this->auditLogger->log(
            entity: 'payment',
            entityId: $paymentId,
            action: 'voided',
            tenantId: $payment->getTenantId(),
            metadata: ['reason' => $reason]
        );

        $this->logger->warning("Payment voided: {$paymentId} - {$reason}");
    }

    /**
     * Post payment to GL.
     *
     * @param PaymentInterface $payment
     * @return string GL journal ID
     */
    private function postPaymentToGL(PaymentInterface $payment): string
    {
        $lines = [];

        // Credit bank account
        $lines[] = [
            'account' => $payment->getBankAccount(),
            'debit' => 0.0,
            'credit' => $payment->getAmount(),
            'description' => "Payment {$payment->getPaymentNumber()}",
        ];

        // Debit AP control account for each allocation
        foreach ($payment->getAllocations() as $allocation) {
            $bill = $this->billRepository->findById($allocation['bill_id']);
            $lines[] = [
                'account' => '2100', // AP Control (should be configurable)
                'debit' => $allocation['amount'],
                'credit' => 0.0,
                'description' => "Payment for bill {$bill->getBillNumber()}",
            ];
        }

        // Post journal via Finance module
        $journalId = $this->financeManager->postJournal(
            tenantId: $payment->getTenantId(),
            journalDate: $payment->getPaymentDate(),
            description: "Vendor payment {$payment->getPaymentNumber()}",
            lines: $lines
        );

        return $journalId;
    }

    /**
     * Generate unique payment number.
     *
     * @param string $tenantId
     * @return string
     */
    private function generatePaymentNumber(string $tenantId): string
    {
        $date = new \DateTime();
        $prefix = 'PAY';
        $datePart = $date->format('Ymd');
        $sequence = str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$datePart}-{$sequence}";
    }
}
