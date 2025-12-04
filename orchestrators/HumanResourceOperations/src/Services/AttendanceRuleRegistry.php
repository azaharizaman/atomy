<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Services;

use Nexus\HumanResourceOperations\Contracts\AttendanceRuleInterface;
use Nexus\HumanResourceOperations\DTOs\AttendanceContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Registry for attendance validation rules
 */
final readonly class AttendanceRuleRegistry
{
    /**
     * @param array<AttendanceRuleInterface> $rules
     */
    public function __construct(
        private array $rules,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Validate attendance against all rules
     * 
     * @return array<RuleCheckResult>
     */
    public function validate(AttendanceContext $context): array
    {
        $results = [];

        foreach ($this->rules as $rule) {
            $this->logger->debug('Checking attendance rule', [
                'rule' => $rule->getName(),
                'employee_id' => $context->employeeId
            ]);

            $results[] = $rule->check($context);
        }

        return $results;
    }

    /**
     * Check if all rules passed
     */
    public function allPassed(array $results): bool
    {
        foreach ($results as $result) {
            if (!$result->passed) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get failed rules
     * 
     * @return array<RuleCheckResult>
     */
    public function getFailures(array $results): array
    {
        return array_filter($results, fn(RuleCheckResult $r) => !$r->passed);
    }

    /**
     * Get all anomalies from results
     */
    public function getAnomalies(array $results): array
    {
        $anomalies = [];

        foreach ($results as $result) {
            if (!$result->passed) {
                $anomalies[] = [
                    'rule' => $result->ruleName,
                    'message' => $result->message,
                    'metadata' => $result->metadata
                ];
            }
        }

        return $anomalies;
    }
}
