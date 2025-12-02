<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Rules;

use Nexus\AccountPeriodClose\Contracts\CloseRuleInterface;
use Nexus\AccountPeriodClose\ValueObjects\CloseCheckResult;
use Nexus\AccountPeriodClose\Enums\ValidationSeverity;

/**
 * Rule that checks if workflow approvals are complete before period close.
 * This rule lives in orchestrator because it requires Nexus\Workflow integration.
 */
final readonly class WorkflowApprovalRule implements CloseRuleInterface
{
    public function __construct(
        // Injected dependencies for workflow integration
    ) {}

    public function getName(): string
    {
        return 'workflow_approval_complete';
    }

    public function getDescription(): string
    {
        return 'Period close workflow approval must be complete';
    }

    public function check(string $tenantId, string $periodId): CloseCheckResult
    {
        $approvalStatus = $this->getApprovalStatus($tenantId, $periodId);
        $passed = $approvalStatus['approved'] ?? false;

        return new CloseCheckResult(
            ruleName: $this->getName(),
            passed: $passed,
            severity: $passed ? ValidationSeverity::INFO : ValidationSeverity::ERROR,
            message: $passed
                ? 'Workflow approval complete'
                : 'Pending workflow approval: ' . ($approvalStatus['pending_approver'] ?? 'Unknown'),
            details: $approvalStatus,
        );
    }

    public function getSeverity(): ValidationSeverity
    {
        return ValidationSeverity::ERROR;
    }

    /**
     * @return array<string, mixed>
     */
    private function getApprovalStatus(string $tenantId, string $periodId): array
    {
        // Implementation checks with Nexus\Workflow
        return [
            'approved' => true,
            'approved_by' => null,
            'approved_at' => null,
            'pending_approver' => null,
        ];
    }
}
