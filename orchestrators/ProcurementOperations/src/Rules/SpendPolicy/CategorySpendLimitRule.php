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
 * Rule to enforce category spend limits.
 *
 * Validates that the transaction does not exceed the configured
 * spend limit for the procurement category.
 */
final readonly class CategorySpendLimitRule implements SpendPolicyRuleInterface
{
    private const string NAME = 'category_spend_limit';

    /**
     * @inheritDoc
     */
    public function check(SpendPolicyContext $context): RuleResult
    {
        // Skip if no limit defined
        if ($context->categoryLimit === null) {
            return RuleResult::pass(self::NAME, 'No category limit defined');
        }

        // Check if limit would be exceeded
        if (!$context->wouldExceedCategoryLimit()) {
            $remaining = $context->categoryLimit->subtract($context->getProjectedCategorySpend());
            return RuleResult::pass(self::NAME, sprintf(
                'Within category limit. Remaining: %s',
                $remaining->format()
            ));
        }

        // Determine severity based on how much over limit
        $projectedSpend = $context->getProjectedCategorySpend();
        $overagePercent = ($projectedSpend->getAmountInMinorUnits() - $context->categoryLimit->getAmountInMinorUnits())
            / $context->categoryLimit->getAmountInMinorUnits() * 100;

        $severity = match (true) {
            $overagePercent >= 50 => PolicyViolationSeverity::CRITICAL,
            $overagePercent >= 20 => PolicyViolationSeverity::ERROR,
            $overagePercent >= 10 => PolicyViolationSeverity::WARNING,
            default => PolicyViolationSeverity::WARNING,
        };

        $violation = SpendPolicyViolation::categoryLimitExceeded(
            threshold: $context->categoryLimit,
            actual: $projectedSpend,
            categoryId: $context->request->categoryId,
            severity: $severity,
        );

        return RuleResult::fail(
            self::NAME,
            $violation->message,
            [
                'violation' => $violation,
                'overage_percent' => round($overagePercent, 2),
            ]
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
        return SpendPolicyType::CATEGORY_LIMIT->value;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SpendPolicyContext $context): bool
    {
        return $context->isPolicyEnabled(SpendPolicyType::CATEGORY_LIMIT->value);
    }
}
