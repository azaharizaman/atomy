<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Services;

use Nexus\AccountPeriodClose\Contracts\CloseDataProviderInterface;
use Nexus\AccountPeriodClose\Contracts\CloseReadinessValidatorInterface;
use Nexus\AccountPeriodClose\Contracts\CloseRuleInterface;
use Nexus\AccountPeriodClose\Enums\ValidationSeverity;
use Nexus\AccountPeriodClose\ValueObjects\CloseReadinessResult;
use Nexus\AccountPeriodClose\ValueObjects\CloseValidationIssue;

/**
 * Pure validation logic for period close readiness.
 */
final readonly class CloseReadinessValidator implements CloseReadinessValidatorInterface
{
    public function __construct(
        private ?CloseDataProviderInterface $dataProvider = null,
    ) {}

    public function validate(string $periodId, array $rules = []): CloseReadinessResult
    {
        $issues = [];
        $warnings = [];
        $context = $this->buildContext($periodId);

        foreach ($rules as $rule) {
            $result = $rule->check($context);
            if (!$result->hasPassed()) {
                $issue = new CloseValidationIssue(
                    ruleId: $rule->getId(),
                    message: $result->getMessage() ?? 'Validation failed',
                    severity: $rule->getSeverity(),
                    resolution: null
                );

                if ($rule->getSeverity() === ValidationSeverity::WARNING) {
                    $warnings[] = $result->getMessage();
                } else {
                    $issues[] = $issue;
                }
            }
        }

        $isReady = count(array_filter($issues, fn($i) => $i->isBlocking())) === 0;

        return new CloseReadinessResult(
            isReady: $isReady,
            issues: $issues,
            warnings: $warnings
        );
    }

    public function getIssues(string $periodId): array
    {
        $result = $this->validate($periodId);
        return $result->getIssues();
    }

    private function buildContext(string $periodId): array
    {
        $context = ['period_id' => $periodId];

        if ($this->dataProvider !== null) {
            $context['trial_balance'] = $this->dataProvider->getTrialBalance($periodId);
            $context['unposted_entries'] = $this->dataProvider->getUnpostedEntries($periodId);
            $context['reconciliations_complete'] = $this->dataProvider->areReconciliationsComplete($periodId);
        }

        return $context;
    }
}
