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
 * Rule to enforce preferred vendor policy.
 *
 * Validates that purchases are made from preferred vendors
 * when the policy requires it for the category.
 */
final readonly class PreferredVendorRule implements SpendPolicyRuleInterface
{
    private const string NAME = 'preferred_vendor';

    /**
     * @inheritDoc
     */
    public function check(SpendPolicyContext $context): RuleResult
    {
        // Skip if no vendor in request
        if (!$context->request->hasVendor()) {
            return RuleResult::pass(self::NAME, 'No vendor specified');
        }

        // Check if vendor is preferred
        if ($context->vendorIsPreferred) {
            return RuleResult::pass(self::NAME, 'Vendor is a preferred vendor for this category');
        }

        // Check if preferred vendor is required for this category
        $requiredCategories = $context->getPolicySetting('preferred_vendor_required_categories', []);
        $isRequired = in_array($context->request->categoryId, $requiredCategories, true);

        if (!$isRequired) {
            return RuleResult::pass(
                self::NAME,
                'Non-preferred vendor allowed for this category',
                ['is_preferred' => false]
            );
        }

        // Preferred vendor required but not using one
        $violation = SpendPolicyViolation::preferredVendorRequired(
            categoryId: $context->request->categoryId,
            severity: PolicyViolationSeverity::WARNING,
        );

        return RuleResult::fail(
            self::NAME,
            $violation->message,
            ['violation' => $violation]
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
        return SpendPolicyType::PREFERRED_VENDOR->value;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SpendPolicyContext $context): bool
    {
        return $context->request->hasVendor()
            && $context->isPolicyEnabled(SpendPolicyType::PREFERRED_VENDOR->value);
    }
}
