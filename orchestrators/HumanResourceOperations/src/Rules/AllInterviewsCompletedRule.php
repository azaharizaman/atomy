<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Rules;

use Nexus\HumanResourceOperations\DTOs\ApplicationContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;
use Nexus\HumanResourceOperations\Contracts\HiringRuleInterface;

/**
 * Rule: Candidate must have completed all required interview stages.
 * 
 * Following Advanced Orchestrator Pattern:
 * - Single Responsibility: One rule = one constraint
 * - Isolated and testable
 * - Reusable across different coordinators
 */
final readonly class AllInterviewsCompletedRule implements HiringRuleInterface
{
    public function getName(): string
    {
        return 'all_interviews_completed';
    }

    public function getDescription(): string
    {
        return 'All required interview stages must be completed before hiring decision';
    }

    public function check(ApplicationContext $context): RuleCheckResult
    {
        // Check if all required interviews are completed
        $interviewResults = $context->interviewResults ?? [];
        
        if (empty($interviewResults)) {
            return new RuleCheckResult(
                ruleName: $this->getName(),
                passed: false,
                severity: 'ERROR',
                message: 'No interview results found',
            );
        }

        // Check if all stages are completed
        $incompleteStages = [];
        foreach ($interviewResults as $stage => $result) {
            if (!($result['completed'] ?? false)) {
                $incompleteStages[] = $stage;
            }
        }

        $passed = empty($incompleteStages);

        return new RuleCheckResult(
            ruleName: $this->getName(),
            passed: $passed,
            severity: $passed ? 'INFO' : 'ERROR',
            message: $passed 
                ? 'All interview stages completed' 
                : 'Incomplete interview stages: ' . implode(', ', $incompleteStages),
        );
    }
}
