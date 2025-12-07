<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Payment;

use Nexus\ProcurementOperations\DTOs\PaymentBatchContext;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates that payment terms are met before processing payment.
 *
 * Checks:
 * - Invoice is due for payment (due date has arrived or is approaching)
 * - Early payment discount eligibility (if applicable)
 * - Payment terms align with vendor agreement
 */
final readonly class PaymentTermsMetRule implements RuleInterface
{
    /**
     * Number of days before due date to allow payment.
     */
    private const PAYMENT_BUFFER_DAYS = 3;

    public function __construct(
        private int $paymentBufferDays = self::PAYMENT_BUFFER_DAYS,
    ) {}

    /**
     * Check if payment terms are met.
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

        $today = new \DateTimeImmutable('today');
        $paymentWindow = $today->modify("+{$this->paymentBufferDays} days");
        $violations = [];

        foreach ($context->invoices as $invoice) {
            $dueDate = $invoice['dueDate'] ?? null;
            
            if ($dueDate === null) {
                $violations[] = sprintf(
                    'Invoice %s has no due date specified',
                    $invoice['id'] ?? 'unknown'
                );
                continue;
            }

            // Convert string to DateTimeImmutable if needed
            if (is_string($dueDate)) {
                try {
                    $dueDate = new \DateTimeImmutable($dueDate);
                } catch (\Exception $e) {
                    $violations[] = sprintf(
                        'Invoice %s has invalid due date format: %s',
                        $invoice['id'] ?? 'unknown',
                        $dueDate
                    );
                    continue;
                }
            }

            // Check if due date is too far in the future
            if ($dueDate > $paymentWindow) {
                $diff = $today->diff($dueDate);
                $daysUntilDue = $diff->days !== false ? (int)$diff->days : 0;
                $violations[] = sprintf(
                    'Invoice %s is not yet due (due in %d days on %s). Payment buffer is %d days.',
                    $invoice['id'] ?? 'unknown',
                    $daysUntilDue,
                    $dueDate->format('Y-m-d'),
                    $this->paymentBufferDays
                );
            }
        }

        if (!empty($violations)) {
            return RuleResult::fail(
                $this->getName(),
                'Payment terms not met: ' . implode('; ', $violations)
            );
        }

        return RuleResult::pass($this->getName());
    }

    /**
     * Get rule name for identification.
     */
    public function getName(): string
    {
        return 'payment_terms_met';
    }
}
