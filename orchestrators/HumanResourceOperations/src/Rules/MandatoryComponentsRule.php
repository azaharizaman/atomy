<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Rules;

use Nexus\HumanResourceOperations\Contracts\PayrollRuleInterface;
use Nexus\HumanResourceOperations\DTOs\PayrollContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;

/**
 * Ensures all mandatory payroll components are present
 */
final readonly class MandatoryComponentsRule implements PayrollRuleInterface
{
    private const array MANDATORY_EARNINGS = ['basic_salary'];
    private const array MANDATORY_DEDUCTIONS = ['income_tax', 'employee_provident_fund'];

    public function check(PayrollContext $context): RuleCheckResult
    {
        $missingComponents = [];
        
        // Check mandatory earnings
        foreach (self::MANDATORY_EARNINGS as $component) {
            if (!isset($context->earnings[$component])) {
                $missingComponents[] = "Missing earning component: {$component}";
            }
        }
        
        // Check mandatory deductions
        foreach (self::MANDATORY_DEDUCTIONS as $component) {
            if (!isset($context->deductions[$component])) {
                $missingComponents[] = "Missing deduction component: {$component}";
            }
        }
        
        $passed = empty($missingComponents);
        
        return new RuleCheckResult(
            passed: $passed,
            ruleName: $this->getName(),
            message: $passed 
                ? 'All mandatory components present'
                : implode('; ', $missingComponents),
            metadata: ['missing_components' => $missingComponents]
        );
    }

    public function getName(): string
    {
        return 'Mandatory Components Rule';
    }
}
