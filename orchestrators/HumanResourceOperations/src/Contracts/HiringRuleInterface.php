<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Contracts;

use Nexus\HumanResourceOperations\DTOs\ApplicationContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;

/**
 * Interface for hiring validation rules.
 * 
 * Following Advanced Orchestrator Pattern: Composable validation.
 */
interface HiringRuleInterface
{
    public function getName(): string;

    public function getDescription(): string;

    public function check(ApplicationContext $context): RuleCheckResult;
}
