<?php

declare(strict_types=1);

namespace App\Services\Receivable;

use App\Models\PaymentReceipt;
use Nexus\Receivable\Contracts\CustomerInvoiceRepositoryInterface;
use Nexus\Receivable\Contracts\PaymentAllocationStrategyInterface;
use Nexus\Receivable\Contracts\PaymentProcessorInterface;
use Nexus\Receivable\Contracts\PaymentReceiptInterface;
use Nexus\Receivable\Contracts\PaymentReceiptRepositoryInterface;
use Nexus\Receivable\Enums\PaymentReceiptStatus;
use Nexus\Receivable\Exceptions\InvalidPaymentException;
use Nexus\Sequencing\Contracts\SequencingManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Payment Processor Service
 *
 * Handles payment receipt creation and allocation to invoices.
 */
final readonly class PaymentProcessor implements PaymentProcessorInterface
{
    public function __construct(
        private PaymentReceiptRepositoryInterface $receiptRepository,
        private CustomerInvoiceRepositoryInterface $invoiceRepository,
        private SequencingManagerInterface $sequencingManager,
        private LoggerInterface $logger
    ) {}

    public function createPaymentReceipt(
        string $tenantId,
        string $customerId,
        float $amount,
        string $currency,
        \DateTimeInterface $receiptDate,
        string $paymentMethod,
        ?string $referenceNumber = null,
        ?string $notes = null
    ): PaymentReceiptInterface {
        if ($amount <= 0) {
            throw InvalidPaymentException::invalidAmount($amount);
        }

        // Generate receipt number
        $receiptNumber = $this->sequencingManager->getNextSequence(
            'payment_receipt',
            $tenantId
        );

        $receipt = new PaymentReceipt([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'receipt_number' => $receiptNumber,
            'amount' => $amount,
            'currency' => $currency,
            'receipt_date' => $receiptDate,
            'payment_method' => $paymentMethod,
            'reference_number' => $referenceNumber,
            'notes' => $notes,
            'status' => PaymentReceiptStatus::DRAFT->value,
            'allocations' => [],
        ]);

        $this->receiptRepository->save($receipt);

        $this->logger->info('Payment receipt created', [
            'receipt_id' => $receipt->getId(),
            'receipt_number' => $receiptNumber,
            'customer_id' => $customerId,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        return $receipt;
    }

    public function allocatePayment(
        string $receiptId,
        PaymentAllocationStrategyInterface $strategy
    ): void {
        $receipt = $this->receiptRepository->getById($receiptId);

        if (!$receipt->getStatus()->canBeAllocated()) {
            throw InvalidPaymentException::cannotAllocate($receiptId, $receipt->getStatus());
        }

        // Get open invoices for customer
        $openInvoices = $this->invoiceRepository->getOpenInvoices($receipt->getCustomerId());

        if (empty($openInvoices)) {
            $this->logger->warning('No open invoices found for payment allocation', [
                'receipt_id' => $receiptId,
                'customer_id' => $receipt->getCustomerId(),
            ]);
            
            // Mark as UNAPPLIED since there are no invoices to apply to
            if ($receipt instanceof PaymentReceipt) {
                $receipt->status = PaymentReceiptStatus::UNAPPLIED->value;
                $this->receiptRepository->save($receipt);
            }
            
            return;
        }

        // Use strategy to allocate payment
        $allocations = $strategy->allocate($receipt->getAmount(), $openInvoices);

        // Update receipt with allocations
        if ($receipt instanceof PaymentReceipt) {
            $receipt->allocations = $allocations;
            $receipt->status = PaymentReceiptStatus::APPLIED->value;
            $this->receiptRepository->save($receipt);
        }

        // Update invoice outstanding balances
        foreach ($allocations as $invoiceId => $allocationAmount) {
            $invoice = $this->invoiceRepository->getById($invoiceId);
            $newOutstandingBalance = $invoice->getOutstandingBalance() - $allocationAmount;
            
            // Update using repository (which should trigger status changes)
            $this->invoiceRepository->updateOutstandingBalance($invoiceId, $newOutstandingBalance);
        }

        $this->logger->info('Payment allocated', [
            'receipt_id' => $receiptId,
            'strategy' => $strategy->getName(),
            'allocations' => $allocations,
            'total_allocated' => array_sum($allocations),
        ]);
    }

    public function voidPaymentReceipt(string $receiptId, string $reason): void
    {
        $receipt = $this->receiptRepository->getById($receiptId);

        if (!$receipt->getStatus()->canBeVoided()) {
            throw InvalidPaymentException::cannotVoid($receiptId, $receipt->getStatus());
        }

        // Reverse allocations if payment was applied
        if ($receipt->getStatus() === PaymentReceiptStatus::APPLIED) {
            $allocations = $receipt->getAllocations();
            
            foreach ($allocations as $invoiceId => $allocationAmount) {
                $invoice = $this->invoiceRepository->getById($invoiceId);
                $newOutstandingBalance = $invoice->getOutstandingBalance() + $allocationAmount;
                
                $this->invoiceRepository->updateOutstandingBalance($invoiceId, $newOutstandingBalance);
            }
        }

        // Mark as voided
        if ($receipt instanceof PaymentReceipt) {
            $receipt->status = PaymentReceiptStatus::VOIDED->value;
            $receipt->notes = ($receipt->notes ?? '') . "\n\nVoided: {$reason}";
            $this->receiptRepository->save($receipt);
        }

        $this->logger->info('Payment receipt voided', [
            'receipt_id' => $receiptId,
            'reason' => $reason,
        ]);
    }

    public function getUnallocatedAmount(string $receiptId): float
    {
        $receipt = $this->receiptRepository->getById($receiptId);
        return $receipt->getUnallocatedAmount();
    }
}
