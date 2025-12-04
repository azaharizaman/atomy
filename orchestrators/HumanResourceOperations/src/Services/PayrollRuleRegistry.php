<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Services;

use Nexus\HumanResourceOperations\Contracts\PayrollRuleInterface;
use Nexus\HumanResourceOperations\DTOs\PayrollContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Registry for payroll validation rules
 */
final readonly class PayrollRuleRegistry
{
    /**
     * @param array<PayrollRuleInterface> $rules
     */
    public function __construct(
        private array $rules,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Validate payroll against all rules
     * 
     * @return array<RuleCheckResult>
     */
    public function validate(PayrollContext $context): array
    {
        $results = [];

        foreach ($this->rules as $rule) {
            $this->logger->debug('Checking payroll rule', [
                'rule' => $rule->getName(),
                'employee_id' => $context->employeeId,
                'period_id' => $context->periodId
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
     * Get validation messages
     */
    public function getValidationMessages(array $results): array
    {
        $messages = [];

        foreach ($results as $result) {
            if (!$result->passed) {
                $messages[] = [
                    'rule' => $result->ruleName,
                    'message' => $result->message,
                    'severity' => 'warning',
                    'metadata' => $result->metadata
                ];
            }
        }

        return $messages;
    }
}
