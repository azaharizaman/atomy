<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Contracts;

use Nexus\AccountPeriodClose\ValueObjects\CloseReadinessResult;

/**
 * Contract for validating period close readiness.
 */
interface CloseReadinessValidatorInterface
{
    /**
     * Validate if a period is ready to be closed.
     *
     * @param string $periodId
     * @param array<CloseRuleInterface> $rules
     * @return CloseReadinessResult
     */
    public function validate(string $periodId, array $rules = []): CloseReadinessResult;

    /**
     * Get all validation issues for a period.
     *
     * @param string $periodId
     * @return array<\Nexus\AccountPeriodClose\ValueObjects\CloseValidationIssue>
     */
    public function getIssues(string $periodId): array;
}
