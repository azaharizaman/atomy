<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services\Memo;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\Payable\Contracts\VendorInvoiceQueryInterface;
use Nexus\Payable\Contracts\VendorInvoicePersistInterface;
use Nexus\ProcurementOperations\Contracts\MemoQueryInterface;
use Nexus\ProcurementOperations\Contracts\MemoPersistInterface;
use Nexus\ProcurementOperations\DTOs\MemoApplicationRequest;
use Nexus\ProcurementOperations\Enums\MemoStatus;
use Nexus\ProcurementOperations\Enums\MemoType;
use Nexus\ProcurementOperations\Events\MemoAppliedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for applying credit memos to invoices.
 *
 * Handles the allocation of credit memo amounts to reduce invoice balances.
 */
final readonly class MemoApplicationService
{
    public function __construct(
        private MemoQueryInterface $memoQuery,
        private MemoPersistInterface $memoPersist,
        private VendorInvoiceQueryInterface $invoiceQuery,
        private VendorInvoicePersistInterface $invoicePersist,
        private EventDispatcherInterface $eventDispatcher,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Apply a credit memo to an invoice.
     *
     * @throws \RuntimeException If memo or invoice not found
     * @throws \InvalidArgumentException If memo cannot be applied
     */
    public function apply(MemoApplicationRequest $request): MemoAppliedEvent
    {
        // Fetch memo and validate
        $memo = $this->memoQuery->findById($request->memoId);
        if ($memo === null) {
            throw new \RuntimeException("Memo {$request->memoId} not found");
        }

        if (!$memo->getStatus()->canApply()) {
            throw new \InvalidArgumentException(
                "Memo {$memo->getNumber()} cannot be applied (status: {$memo->getStatus()->value})"
            );
        }

        if ($memo->getType() !== MemoType::CREDIT) {
            throw new \InvalidArgumentException(
                "Only credit memos can be applied to invoices. Got: {$memo->getType()->value}"
            );
        }

        // Fetch invoice and validate
        $invoice = $this->invoiceQuery->findById($request->invoiceId);
        if ($invoice === null) {
            throw new \RuntimeException("Invoice {$request->invoiceId} not found");
        }

        // Validate vendor match
        if ($memo->getVendorId() !== $invoice->getVendorId()) {
            throw new \InvalidArgumentException(
                'Memo vendor does not match invoice vendor'
            );
        }

        // Validate amounts
        $memoRemaining = $memo->getRemainingAmount();
        $invoiceRemaining = $invoice->getRemainingAmount();

        if ($request->amount->greaterThan($memoRemaining)) {
            throw new \InvalidArgumentException(
                "Application amount exceeds memo remaining balance"
            );
        }

        if ($request->amount->greaterThan($invoiceRemaining)) {
            throw new \InvalidArgumentException(
                "Application amount exceeds invoice remaining balance"
            );
        }

        $this->logger->info('Applying credit memo to invoice', [
            'memo_id' => $request->memoId,
            'invoice_id' => $request->invoiceId,
            'amount' => $request->amount->getAmount(),
        ]);

        // Apply the memo
        $newMemoRemaining = $memoRemaining->subtract($request->amount);
        $newInvoiceRemaining = $invoiceRemaining->subtract($request->amount);

        // Update memo
        $newMemoStatus = $newMemoRemaining->isZero()
            ? MemoStatus::APPLIED
            : MemoStatus::PARTIALLY_APPLIED;

        $memo->applyAmount($request->amount, $newMemoStatus);
        $this->memoPersist->update($memo);

        // Update invoice
        $invoice->applyCredit($request->amount, $request->memoId);
        $this->invoicePersist->update($invoice);

        // Create event
        $event = new MemoAppliedEvent(
            tenantId: $request->tenantId,
            memoId: $request->memoId,
            memoNumber: $memo->getNumber(),
            invoiceId: $request->invoiceId,
            invoiceNumber: $invoice->getNumber(),
            appliedAmount: $request->amount,
            remainingMemoAmount: $newMemoRemaining,
            remainingInvoiceAmount: $newInvoiceRemaining,
            appliedBy: $request->appliedBy,
            appliedAt: new \DateTimeImmutable()
        );

        $this->eventDispatcher->dispatch($event);

        // Audit log
        $this->auditLogger->log(
            entityId: $request->memoId,
            action: 'memo_applied',
            description: sprintf(
                'Credit memo %s applied to invoice %s for %s %s',
                $memo->getNumber(),
                $invoice->getNumber(),
                $request->amount->getCurrency(),
                number_format($request->amount->getAmount(), 2)
            ),
            metadata: [
                'invoice_id' => $request->invoiceId,
                'applied_amount' => $request->amount->getAmount(),
                'remaining_memo' => $newMemoRemaining->getAmount(),
                'remaining_invoice' => $newInvoiceRemaining->getAmount(),
            ]
        );

        return $event;
    }

    /**
     * Auto-apply credit memos to open invoices for a vendor.
     *
     * Applies oldest credit memos to oldest invoices (FIFO).
     *
     * @return array<MemoAppliedEvent> Events for each application
     */
    public function autoApplyForVendor(
        string $tenantId,
        string $vendorId,
        string $appliedBy
    ): array {
        $events = [];

        // Get unapplied credit memos (oldest first)
        $memos = $this->memoQuery->findUnappliedByVendor($vendorId);

        // Get open invoices (oldest first)
        $invoices = $this->invoiceQuery->findOpenByVendor($vendorId);

        foreach ($memos as $memo) {
            if ($memo->getType() !== MemoType::CREDIT || !$memo->getStatus()->canApply()) {
                continue;
            }

            $memoRemaining = $memo->getRemainingAmount();

            foreach ($invoices as $invoice) {
                if ($memoRemaining->isZero()) {
                    break;
                }

                $invoiceRemaining = $invoice->getRemainingAmount();
                if ($invoiceRemaining->isZero()) {
                    continue;
                }

                // Apply minimum of memo remaining and invoice remaining
                $applyAmount = $memoRemaining->lessThanOrEqual($invoiceRemaining)
                    ? $memoRemaining
                    : $invoiceRemaining;

                try {
                    $event = $this->apply(new MemoApplicationRequest(
                        tenantId: $tenantId,
                        memoId: $memo->getId(),
                        invoiceId: $invoice->getId(),
                        amount: $applyAmount,
                        appliedBy: $appliedBy,
                        notes: 'Auto-applied'
                    ));

                    $events[] = $event;
                    $memoRemaining = $event->remainingMemoAmount;

                } catch (\Exception $e) {
                    $this->logger->warning('Failed to auto-apply memo', [
                        'memo_id' => $memo->getId(),
                        'invoice_id' => $invoice->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $events;
    }
}
