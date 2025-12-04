<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Rules;

use Nexus\HumanResourceOperations\DTOs\ApplicationContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;
use Nexus\HumanResourceOperations\Contracts\HiringRuleInterface;

/**
 * Rule: Candidate must meet minimum qualifications for the position.
 */
final readonly class MeetsMinimumQualificationsRule implements HiringRuleInterface
{
    public function getName(): string
    {
        return 'meets_minimum_qualifications';
    }

    public function getDescription(): string
    {
        return 'Candidate must meet the minimum qualifications defined for the position';
    }

    public function check(ApplicationContext $context): RuleCheckResult
    {
        $qualifications = $context->qualifications ?? [];
        
        if (empty($qualifications)) {
            return new RuleCheckResult(
                ruleName: $this->getName(),
                passed: false,
                severity: 'ERROR',
                message: 'No qualification data available',
            );
        }

        // Check required qualifications
        $missingQualifications = [];
        foreach ($qualifications as $qualification => $requirement) {
            if ($requirement['required'] && !($requirement['met'] ?? false)) {
                $missingQualifications[] = $qualification;
            }
        }

        $passed = empty($missingQualifications);

        return new RuleCheckResult(
            ruleName: $this->getName(),
            passed: $passed,
            severity: $passed ? 'INFO' : 'ERROR',
            message: $passed 
                ? 'All minimum qualifications met' 
                : 'Missing qualifications: ' . implode(', ', $missingQualifications),
        );
    }
}
