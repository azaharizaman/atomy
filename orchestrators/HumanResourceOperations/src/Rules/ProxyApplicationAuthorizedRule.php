<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Rules;

use Nexus\HumanResourceOperations\DTOs\LeaveContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;
use Nexus\HumanResourceOperations\Contracts\LeaveRuleInterface;
use Nexus\Identity\Contracts\PolicyEvaluatorInterface;
use Nexus\Identity\Contracts\UserQueryInterface;

/**
 * Rule: Applicant must have authorization to apply leave on behalf of employee.
 * 
 * This rule validates that when a user applies leave on behalf of another employee,
 * they have the necessary permission and context-aware authorization to do so.
 * Self-applications bypass this check.
 * 
 * Uses PolicyEvaluatorInterface for context-aware authorization.
 */
final readonly class ProxyApplicationAuthorizedRule implements LeaveRuleInterface
{
    public function __construct(
        private PolicyEvaluatorInterface $policyEvaluator,
        private UserQueryInterface $userQuery
    ) {}

    public function getName(): string
    {
        return 'proxy_application_authorized';
    }

    public function getDescription(): string
    {
        return 'Applicant must have permission and authorization to apply leave on behalf of employee';
    }

    public function check(LeaveContext $context): RuleCheckResult
    {
        // Skip validation if self-application
        if (!$context->isProxyApplication) {
            return new RuleCheckResult(
                ruleName: $this->getName(),
                passed: true,
                severity: 'INFO',
                message: 'Self-application: no proxy authorization required',
                details: [
                    'applicant_user_id' => $context->applicantUserId,
                    'employee_id' => $context->employeeId,
                    'is_proxy' => false,
                ],
            );
        }

        // Get applicant user to evaluate policy
        $applicant = $this->userQuery->findById($context->applicantUserId);
        
        if ($applicant === null) {
            return new RuleCheckResult(
                ruleName: $this->getName(),
                passed: false,
                severity: 'ERROR',
                message: sprintf('Applicant user not found: %s', $context->applicantUserId),
                details: [
                    'applicant_user_id' => $context->applicantUserId,
                ],
            );
        }

        // Evaluate policy with context (checks permission + business rules)
        $canApplyOnBehalf = $this->policyEvaluator->evaluate(
            user: $applicant,
            action: 'hrm.leave.apply_on_behalf',
            resource: null,
            context: [
                'target_employee_id' => $context->employeeId,
                'applicant_name' => $context->applicantName,
                'employee_name' => $context->employeeName,
            ]
        );

        return new RuleCheckResult(
            ruleName: $this->getName(),
            passed: $canApplyOnBehalf,
            severity: $canApplyOnBehalf ? 'INFO' : 'ERROR',
            message: $canApplyOnBehalf
                ? sprintf(
                    'Applicant %s authorized to apply leave on behalf of %s',
                    $context->applicantName,
                    $context->employeeName
                )
                : sprintf(
                    'Applicant %s does not have permission or authorization to apply leave on behalf of %s',
                    $context->applicantName,
                    $context->employeeName
                ),
            details: [
                'applicant_user_id' => $context->applicantUserId,
                'applicant_name' => $context->applicantName,
                'employee_id' => $context->employeeId,
                'employee_name' => $context->employeeName,
                'is_proxy' => true,
                'action' => 'hrm.leave.apply_on_behalf',
            ],
        );
    }
}
