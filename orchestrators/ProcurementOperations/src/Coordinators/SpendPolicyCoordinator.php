<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\SpendPolicyDataProviderInterface;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyContext;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyRequest;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyResult;
use Nexus\ProcurementOperations\Enums\PolicyAction;
use Nexus\ProcurementOperations\Rules\SpendPolicy\SpendPolicyRuleRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for spend policy evaluation.
 *
 * Orchestrates the evaluation of procurement transactions against
 * spend policies including category limits, vendor limits, maverick
 * spend detection, and budget availability.
 *
 * @example
 * ```php
 * $request = new SpendPolicyRequest(
 *     tenantId: 'tenant-001',
 *     amount: Money::of(50000, 'USD'),
 *     categoryId: 'IT_HARDWARE',
 *     vendorId: 'vendor-abc',
 *     departmentId: 'dept-engineering',
 *     requestedBy: 'user-123',
 * );
 *
 * $result = $coordinator->evaluate($request);
 *
 * if (!$result->passed) {
 *     // Handle violations based on recommended action
 *     match ($result->recommendedAction) {
 *         PolicyAction::BLOCK => throw new SpendPolicyBlockedException($result->violations),
 *         PolicyAction::REQUIRE_APPROVAL => $this->submitForApproval($result),
 *         PolicyAction::FLAG_FOR_REVIEW => $this->flagForReview($result),
 *         PolicyAction::ALLOW => null, // Continue with warnings
 *     };
 * }
 * ```
 */
final readonly class SpendPolicyCoordinator
{
    public function __construct(
        private SpendPolicyDataProviderInterface $dataProvider,
        private SpendPolicyRuleRegistry $ruleRegistry,
        private AuditLogManagerInterface $auditLogger,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Evaluate a transaction against spend policies.
     *
     * @param SpendPolicyRequest $request The transaction to evaluate
     * @return SpendPolicyResult The evaluation result with any violations
     */
    public function evaluate(SpendPolicyRequest $request): SpendPolicyResult
    {
        $this->logger->debug('Evaluating spend policy', [
            'tenant_id' => $request->tenantId,
            'amount' => $request->amount->format(),
            'category_id' => $request->categoryId,
            'vendor_id' => $request->vendorId,
        ]);

        // Step 1: Build evaluation context (DataProvider does the work)
        $context = $this->dataProvider->buildContext($request);

        // Step 2: Run all applicable policy rules (RuleRegistry does the work)
        $result = $this->ruleRegistry->validate($context);

        // Step 3: Log the evaluation
        $this->logEvaluation($request, $result);

        // Step 4: Dispatch event if there are violations
        if (!$result->passed) {
            $this->dispatchViolationEvent($request, $result);
        }

        return $result;
    }

    /**
     * Evaluate multiple transactions in batch.
     *
     * @param array<SpendPolicyRequest> $requests
     * @return array<SpendPolicyResult>
     */
    public function evaluateBatch(array $requests): array
    {
        return array_map(fn(SpendPolicyRequest $request) => $this->evaluate($request), $requests);
    }

    /**
     * Check if a transaction would pass spend policies without creating audit log.
     *
     * Useful for preview/simulation scenarios.
     *
     * @param SpendPolicyRequest $request The transaction to evaluate
     * @return SpendPolicyResult The evaluation result
     */
    public function preview(SpendPolicyRequest $request): SpendPolicyResult
    {
        $context = $this->dataProvider->buildContext($request);
        return $this->ruleRegistry->validate($context);
    }

    /**
     * Log the policy evaluation to audit trail.
     */
    private function logEvaluation(SpendPolicyRequest $request, SpendPolicyResult $result): void
    {
        $action = $result->passed ? 'policy_check_passed' : 'policy_check_failed';
        $description = $result->passed
            ? sprintf(
                'Spend policy check passed for %s purchase in category %s',
                $request->amount->format(),
                $request->categoryId
            )
            : sprintf(
                'Spend policy check failed with %d violation(s). Action: %s',
                count($result->violations),
                $result->recommendedAction->value
            );

        $this->auditLogger->log(
            entityId: $request->vendorId ?? $request->categoryId,
            action: $action,
            description: $description,
            metadata: [
                'tenant_id' => $request->tenantId,
                'amount' => $request->amount->getAmountInMinorUnits(),
                'currency' => $request->amount->getCurrency(),
                'category_id' => $request->categoryId,
                'vendor_id' => $request->vendorId,
                'department_id' => $request->departmentId,
                'passed' => $result->passed,
                'violation_count' => count($result->violations),
                'recommended_action' => $result->recommendedAction?->value,
                'passed_policies' => $result->passedPolicies,
            ]
        );
    }

    /**
     * Dispatch event for policy violations.
     */
    private function dispatchViolationEvent(SpendPolicyRequest $request, SpendPolicyResult $result): void
    {
        if ($this->eventDispatcher === null) {
            return;
        }

        // Event would be defined in the package
        // $this->eventDispatcher->dispatch(new SpendPolicyViolationEvent($request, $result));

        $this->logger->info('Spend policy violation event dispatched', [
            'tenant_id' => $request->tenantId,
            'violation_count' => count($result->violations),
            'recommended_action' => $result->recommendedAction?->value,
        ]);
    }
}
