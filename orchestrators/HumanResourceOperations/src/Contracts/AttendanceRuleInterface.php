<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Contracts;

use Nexus\HumanResourceOperations\DTOs\AttendanceContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;

/**
 * Contract for attendance validation rules
 */
interface AttendanceRuleInterface
{
    /**
     * Check if attendance record passes this rule
     */
    public function check(AttendanceContext $context): RuleCheckResult;

    /**
     * Get human-readable rule name
     */
    public function getName(): string;
}
