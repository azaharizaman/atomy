<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Rules;

use Nexus\HumanResourceOperations\DTOs\LeaveContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;
use Nexus\HumanResourceOperations\Contracts\LeaveRuleInterface;
use Nexus\HumanResourceOperations\DataProviders\LeaveDataProvider;

/**
 * Rule: Leave dates must not overlap with existing approved leaves.
 */
final readonly class NoOverlappingLeavesRule implements LeaveRuleInterface
{
    public function __construct(
        private LeaveDataProvider $dataProvider
    ) {}

    public function getName(): string
    {
        return 'no_overlapping_leaves';
    }

    public function getDescription(): string
    {
        return 'Leave dates must not overlap with existing approved leaves';
    }

    public function check(LeaveContext $context): RuleCheckResult
    {
        $hasOverlap = $this->dataProvider->hasOverlappingLeaves(
            $context->employeeId,
            $context->startDate,
            $context->endDate
        );

        $passed = !$hasOverlap;

        return new RuleCheckResult(
            ruleName: $this->getName(),
            passed: $passed,
            severity: $passed ? 'INFO' : 'ERROR',
            message: $passed
                ? 'No overlapping leaves found'
                : 'Leave dates overlap with existing approved leave',
        );
    }
}
