<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Payment;

use Nexus\ProcurementOperations\DTOs\PaymentBatchContext;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates that bank details are verified before processing payment.
 *
 * Checks:
 * - Source bank account exists and is active
 * - Vendor bank details are complete (for wire/EFT transfers)
 * - Bank account has sufficient funds (if available)
 * - Currency matches or conversion is allowed
 */
final readonly class BankDetailsVerifiedRule implements RuleInterface
{
    /**
     * Check if bank details are verified.
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

        // Validate source bank account
        $bankInfo = $context->bankAccountInfo;
        
        if (empty($bankInfo)) {
            return RuleResult::fail(
                $this->getName(),
                'No bank account information provided for payment'
            );
        }

        // Check bank account status
        $accountStatus = $bankInfo['status'] ?? null;
        if ($accountStatus !== 'active') {
            $violations[] = sprintf(
                'Bank account %s is not active (status: %s)',
                $bankInfo['accountNumber'] ?? 'unknown',
                $accountStatus ?? 'unknown'
            );
        }

        // Check sufficient funds
        if (!$context->hasSufficientFunds()) {
            $violations[] = sprintf(
                'Insufficient funds: available %s, required %s',
                $this->formatAmount($bankInfo['availableBalanceCents'] ?? 0, $bankInfo['currency'] ?? 'USD'),
                $this->formatAmount($context->netAmountCents, $bankInfo['currency'] ?? 'USD')
            );
        }

        // Validate vendor bank details for each invoice
        foreach ($context->invoices as $invoice) {
            $vendorBankDetails = $invoice['vendorBankDetails'] ?? [];
            $paymentMethod = $invoice['paymentMethod'] ?? 'wire';

            // For electronic payments, bank details are required
            if (in_array($paymentMethod, ['wire', 'eft', 'ach'], true)) {
                if (empty($vendorBankDetails)) {
                    $violations[] = sprintf(
                        'Invoice %s: vendor bank details required for %s payment',
                        $invoice['id'] ?? 'unknown',
                        strtoupper($paymentMethod)
                    );
                    continue;
                }

                // Check required bank fields
                $requiredFields = ['bankName', 'accountNumber'];
                foreach ($requiredFields as $field) {
                    if (empty($vendorBankDetails[$field])) {
                        $violations[] = sprintf(
                            'Invoice %s: vendor bank detail "%s" is missing',
                            $invoice['id'] ?? 'unknown',
                            $field
                        );
                    }
                }

                // For international payments, check SWIFT/IBAN
                if (($vendorBankDetails['isInternational'] ?? false) === true) {
                    if (empty($vendorBankDetails['swiftCode']) && empty($vendorBankDetails['iban'])) {
                        $violations[] = sprintf(
                            'Invoice %s: SWIFT code or IBAN required for international payment',
                            $invoice['id'] ?? 'unknown'
                        );
                    }
                }

                // Check verification status
                if (($vendorBankDetails['isVerified'] ?? false) !== true) {
                    $violations[] = sprintf(
                        'Invoice %s: vendor bank details have not been verified',
                        $invoice['id'] ?? 'unknown'
                    );
                }
            }

            // Currency validation
            $invoiceCurrency = $invoice['currency'] ?? null;
            $bankCurrency = $bankInfo['currency'] ?? null;
            
            if ($invoiceCurrency !== null && $bankCurrency !== null && $invoiceCurrency !== $bankCurrency) {
                $violations[] = sprintf(
                    'Invoice %s: currency mismatch (invoice: %s, bank: %s)',
                    $invoice['id'] ?? 'unknown',
                    $invoiceCurrency,
                    $bankCurrency
                );
            }
        }

        if (!empty($violations)) {
            return RuleResult::fail(
                $this->getName(),
                'Bank details verification failed: ' . implode('; ', $violations)
            );
        }

        return RuleResult::pass($this->getName());
    }

    /**
     * Get rule name for identification.
     */
    public function getName(): string
    {
        return 'bank_details_verified';
    }

    /**
     * Format amount in cents to display string.
     */
    private function formatAmount(int $amountCents, string $currency): string
    {
        return sprintf(
            '%s %.2f',
            $currency,
            $amountCents / 100
        );
    }
}
