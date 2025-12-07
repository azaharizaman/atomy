<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Listeners;

use Nexus\JournalEntry\Contracts\JournalEntryManagerInterface;
use Nexus\Payable\Contracts\VendorLedgerManagerInterface;
use Nexus\ProcurementOperations\Events\PaymentExecutedEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener that updates vendor ledger when payment is executed.
 *
 * Triggered by: PaymentExecutedEvent from ProcurementOperations
 *
 * Workflow:
 * 1. Receives payment executed event
 * 2. Updates vendor ledger with payment applied
 * 3. Posts GL entries (Debit AP, Credit Cash)
 * 4. Marks invoices as paid
 */
final class UpdateVendorLedgerOnPayment
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly ?VendorLedgerManagerInterface $vendorLedger = null,
        private readonly ?JournalEntryManagerInterface $journalEntryManager = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * Handle the payment executed event.
     */
    public function __invoke(PaymentExecutedEvent $event): void
    {
        $this->handle($event);
    }

    /**
     * Handle the payment executed event.
     */
    public function handle(PaymentExecutedEvent $event): void
    {
        $this->getLogger()->info('Processing payment execution for ledger update', [
            'tenantId' => $event->tenantId,
            'paymentId' => $event->paymentId,
            'batchId' => $event->paymentBatchId,
            'amountCents' => $event->totalAmountCents,
        ]);

        // Update vendor ledger
        $this->updateVendorLedger($event);

        // Post GL journal entries
        $this->postJournalEntries($event);

        // Mark invoices as paid
        $this->markInvoicesAsPaid($event);

        $this->logger->info('Vendor ledger updated successfully', [
            'tenantId' => $event->tenantId,
            'paymentId' => $event->paymentId,
            'invoiceCount' => count($event->vendorBillIds),
        ]);
    }

    /**
     * Update vendor ledger with payment allocation.
     */
    private function updateVendorLedger(PaymentExecutedEvent $event): void
    {
        if ($this->vendorLedger === null) {
            $this->logger->debug('Vendor ledger manager not configured, skipping ledger update');
            return;
        }

        foreach ($event->vendorBillIds as $vendorBillId) {
            try {
                $this->vendorLedger->recordPayment(
                    tenantId: $event->tenantId,
                    vendorBillId: $vendorBillId,
                    paymentId: $event->paymentId,
                    amountCents: $this->getInvoicePaymentAmount($vendorBillId, $event),
                    paymentDate: $event->executedAt,
                    bankReference: $event->bankReference,
                );

                $this->logger->debug('Vendor ledger entry recorded', [
                    'vendorBillId' => $vendorBillId,
                    'paymentId' => $event->paymentId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to update vendor ledger', [
                    'vendorBillId' => $vendorBillId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * Post GL journal entries for the payment.
     *
     * Standard AP Payment Entry:
     * - Debit: Accounts Payable (reduces liability)
     * - Credit: Cash/Bank Account (reduces asset)
     * - Debit/Credit: Discounts Taken (if applicable)
     */
    private function postJournalEntries(PaymentExecutedEvent $event): void
    {
        if ($this->journalEntryManager === null) {
            $this->logger->debug('Journal entry manager not configured, skipping GL posting');
            return;
        }

        // Skip if journal entry already created (from event)
        if ($event->journalEntryId !== null) {
            $this->logger->debug('Journal entry already created during payment execution', [
                'journalEntryId' => $event->journalEntryId,
            ]);
            return;
        }

        try {
            // In a real implementation, this would create the journal entry
            // with proper account codes based on tenant configuration
            $journalEntryId = $this->journalEntryManager->post([
                'tenantId' => $event->tenantId,
                'reference' => sprintf('Payment %s', $event->paymentId),
                'date' => $event->executedAt,
                'currency' => $event->currency,
                'lines' => $this->buildJournalLines($event),
                'sourceDocument' => 'payment',
                'sourceDocumentId' => $event->paymentId,
            ]);

            $this->logger->info('Journal entry posted for payment', [
                'journalEntryId' => $journalEntryId,
                'paymentId' => $event->paymentId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to post journal entry', [
                'paymentId' => $event->paymentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark vendor invoices as paid.
     */
    private function markInvoicesAsPaid(PaymentExecutedEvent $event): void
    {
        if ($this->vendorLedger === null) {
            $this->logger->debug('Vendor ledger manager not configured, skipping invoice status update');
            return;
        }

        foreach ($event->vendorBillIds as $vendorBillId) {
            try {
                $this->vendorLedger->markInvoiceAsPaid(
                    tenantId: $event->tenantId,
                    vendorBillId: $vendorBillId,
                    paymentId: $event->paymentId,
                    paidAt: $event->executedAt,
                );

                $this->logger->debug('Invoice marked as paid', [
                    'vendorBillId' => $vendorBillId,
                    'paymentId' => $event->paymentId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to mark invoice as paid', [
                    'vendorBillId' => $vendorBillId,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - continue processing other invoices
            }
        }
    }

    /**
     * Get individual invoice payment amount from batch total.
     */
    private function getInvoicePaymentAmount(string $vendorBillId, PaymentExecutedEvent $event): int
    {
        // In a real implementation, this would look up the allocated amount from batch context.
        // For now, divide evenly and distribute any remainder to the first invoice.
        $invoiceCount = count($event->vendorBillIds);
        if ($invoiceCount === 0) {
            return 0;
        }

        $baseAmount = intdiv($event->totalAmountCents, $invoiceCount);
        $remainder = $event->totalAmountCents % $invoiceCount;

        // Distribute the remainder to the first invoice in the list
        // Check if vendorBillId matches the first element
        $firstBillId = reset($event->vendorBillIds);
        if ($firstBillId !== false && $vendorBillId === $firstBillId) {
            return $baseAmount + $remainder;
        }
        return $baseAmount;
    }

    /**
     * Build journal entry lines for payment.
     *
     * NOTE: Account codes should be configured per tenant via a Chart of Accounts service.
     * These are placeholder values and must be replaced with tenant-specific configuration.
     *
     * @return array<array{accountCode: string, debit: int, credit: int, description: string}>
     */
    private function buildJournalLines(PaymentExecutedEvent $event): array
    {
        // TODO: Use Chart of Accounts service to resolve account codes by purpose
        // e.g., $chartOfAccounts->getAccountCode('accounts_payable')
        // These hardcoded values are placeholders only

        // Standard AP Payment Entry
        return [
            // Debit Accounts Payable
            [
                'accountCode' => '2000', // TODO: Replace with tenant-configured AP account
                'debit' => $event->totalAmountCents,
                'credit' => 0,
                'description' => sprintf('Payment %s - AP Reduction', $event->paymentId),
            ],
            // Credit Cash/Bank
            [
                'accountCode' => '1000', // TODO: Replace with tenant-configured Cash account
                'debit' => 0,
                'credit' => $event->totalAmountCents,
                'description' => sprintf('Payment %s - Cash Disbursement', $event->paymentId),
            ],
        ];
    }
}
