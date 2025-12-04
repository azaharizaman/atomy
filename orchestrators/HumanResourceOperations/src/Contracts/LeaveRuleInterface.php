<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Contracts;

use Nexus\HumanResourceOperations\DTOs\LeaveContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;

/**
 * Interface for leave validation rules.
 */
interface LeaveRuleInterface
{
    public function getName(): string;

    public function getDescription(): string;

    public function check(LeaveContext $context): RuleCheckResult;
}
