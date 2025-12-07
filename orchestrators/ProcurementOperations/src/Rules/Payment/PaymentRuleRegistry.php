<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\Payment;

use Nexus\ProcurementOperations\DTOs\PaymentBatchContext;
use Nexus\ProcurementOperations\Exceptions\PaymentException;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Registry for payment validation rules.
 *
 * Composes multiple payment rules and provides a unified
 * validation interface following the Advanced Orchestrator Pattern v1.1.
 *
 * Rules are executed in order:
 * 1. DuplicatePaymentRule - Prevent duplicate payments
 * 2. PaymentTermsMetRule - Verify payment terms
 * 3. BankDetailsVerifiedRule - Validate bank information
 */
final readonly class PaymentRuleRegistry
{
    public function __construct(
        private DuplicatePaymentRule $duplicatePaymentRule,
        private PaymentTermsMetRule $paymentTermsMetRule,
        private BankDetailsVerifiedRule $bankDetailsVerifiedRule,
    ) {}

    /**
     * Validate a payment batch context against all rules.
     *
     * @throws PaymentException If any validation rule fails
     */
    public function validate(PaymentBatchContext $context): void
    {
        $rules = $this->getRules();

        foreach ($rules as $rule) {
            $result = $rule->check($context);

            if ($result->failed()) {
                throw PaymentException::validationFailed(
                    $result->ruleName,
                    $result->message ?? 'Validation failed'
                );
            }
        }
    }

    /**
     * Validate and collect all results (non-throwing).
     *
     * @return array<RuleResult>
     */
    public function validateAll(PaymentBatchContext $context): array
    {
        $results = [];

        foreach ($this->getRules() as $rule) {
            $results[] = $rule->check($context);
        }

        return $results;
    }

    /**
     * Check if all rules pass.
     */
    public function isValid(PaymentBatchContext $context): bool
    {
        foreach ($this->getRules() as $rule) {
            $result = $rule->check($context);
            if ($result->failed()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get failed rules only.
     *
     * @return array<RuleResult>
     */
    public function getFailedRules(PaymentBatchContext $context): array
    {
        $failed = [];

        foreach ($this->getRules() as $rule) {
            $result = $rule->check($context);
            if ($result->failed()) {
                $failed[] = $result;
            }
        }

        return $failed;
    }

    /**
     * Get ordered list of rules.
     *
     * @return array<DuplicatePaymentRule|PaymentTermsMetRule|BankDetailsVerifiedRule>
     */
    private function getRules(): array
    {
        return [
            $this->duplicatePaymentRule,
            $this->paymentTermsMetRule,
            $this->bankDetailsVerifiedRule,
        ];
    }
}
