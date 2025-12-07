<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Payment;

use Nexus\ProcurementOperations\DTOs\PaymentBatchContext;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates that no duplicate payments are being processed.
 *
 * Checks:
 * - Invoice has not already been paid
 * - Invoice is not already in a pending payment batch
 * - No duplicate invoice IDs in the current batch
 */
final readonly class DuplicatePaymentRule implements RuleInterface
{
    /**
     * Check for duplicate payments.
     *
     * @param PaymentBatchContext $context
     */
    public function check(object $context): RuleResult
    {
        if (!$context instanceof PaymentBatchContext) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type: expected PaymentBatchContext'
            );
        }

        $violations = [];
        $seenInvoiceIds = [];
        $duplicateIds = [];

        foreach ($context->invoices as $invoice) {
            $invoiceId = $invoice['id'] ?? null;
            
            if ($invoiceId === null) {
                $violations[] = 'Invoice missing ID field';
                continue;
            }

            // Check for duplicates within the batch
            if (isset($seenInvoiceIds[$invoiceId])) {
                $duplicateIds[] = $invoiceId;
                $violations[] = sprintf(
                    'Invoice %s appears multiple times in the payment batch',
                    $invoiceId
                );
            } else {
                $seenInvoiceIds[$invoiceId] = true;
            }

            // Check if already paid
            $status = $invoice['status'] ?? null;
            if ($status === 'paid') {
                $violations[] = sprintf(
                    'Invoice %s has already been paid',
                    $invoiceId
                );
            }

            // Check if already in a payment batch
            $paymentBatchId = $invoice['paymentBatchId'] ?? null;
            if ($paymentBatchId !== null && ($invoice['paymentStatus'] ?? null) === 'pending') {
                $violations[] = sprintf(
                    'Invoice %s is already in pending payment batch %s',
                    $invoiceId,
                    $paymentBatchId
                );
            }
        }

        if (!empty($violations)) {
            return RuleResult::fail(
                $this->getName(),
                'Duplicate payment detected: ' . implode('; ', $violations),
                ['duplicates' => array_unique($duplicateIds)]
            );
        }

        return RuleResult::pass($this->getName());
    }

    /**
     * Get rule name for identification.
     */
    public function getName(): string
    {
        return 'duplicate_payment';
    }
}
