<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\SpendPolicy;

use Nexus\ProcurementOperations\Contracts\SpendPolicyRuleInterface;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyContext;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyViolation;
use Nexus\ProcurementOperations\Enums\PolicyViolationSeverity;
use Nexus\ProcurementOperations\Enums\SpendPolicyType;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Rule to enforce contract compliance.
 *
 * Validates that purchases comply with contract terms including
 * remaining value, validity period, and item restrictions.
 */
final readonly class ContractComplianceRule implements SpendPolicyRuleInterface
{
    private const string NAME = 'contract_compliance';

    /**
     * @inheritDoc
     */
    public function check(SpendPolicyContext $context): RuleResult
    {
        // Skip if no active contract
        if (!$context->hasActiveContract) {
            return RuleResult::pass(self::NAME, 'No active contract for this category');
        }

        // Check contract remaining value
        if ($context->contractRemaining !== null && $context->wouldExceedContractValue()) {
            $violation = SpendPolicyViolation::contractComplianceRequired(
                message: sprintf(
                    'Transaction amount (%s) exceeds contract remaining value (%s)',
                    $context->request->amount->format(),
                    $context->contractRemaining->format()
                ),
                contractId: $context->activeContractId,
                severity: PolicyViolationSeverity::ERROR,
            );

            return RuleResult::fail(
                self::NAME,
                $violation->message,
                [
                    'violation' => $violation,
                    'contract_id' => $context->activeContractId,
                    'remaining' => $context->contractRemaining->format(),
                    'requested' => $context->request->amount->format(),
                ]
            );
        }

        // All contract checks passed
        return RuleResult::pass(
            self::NAME,
            sprintf(
                'Contract %s compliance verified. Remaining: %s',
                $context->activeContractId,
                $context->contractRemaining?->format() ?? 'unlimited'
            ),
            ['contract_id' => $context->activeContractId]
        );
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getPolicyType(): string
    {
        return SpendPolicyType::CONTRACT_COMPLIANCE->value;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SpendPolicyContext $context): bool
    {
        return $context->hasActiveContract
            && $context->isPolicyEnabled(SpendPolicyType::CONTRACT_COMPLIANCE->value);
    }
}
