<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\RuleInterface;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Rule to validate that an accounting period is open for posting.
 *
 * This rule ensures that the target period is open and can accept
 * journal entries or other financial transactions.
 *
 * Following Advanced Orchestrator Pattern:
 * - Single responsibility: Period open status validation
 * - Testable in isolation
 * - Reusable across coordinators
 *
 * @see ARCHITECTURE.md Section 4 for rule patterns
 * @since 1.0.0
 */
final readonly class PeriodOpenRule implements RuleInterface
{
    /**
     * @param object $periodManager PeriodManagerInterface for period operations
     */
    public function __construct(
        private object $periodManager,
    ) {}

    /**
     * @inheritDoc
     *
     * @param object $context Context containing tenantId and periodId
     * @return RuleResult The rule check result
     */
    public function check(object $context): RuleResult
    {
        $tenantId = $this->extractTenantId($context);
        $periodId = $this->extractPeriodId($context);

        if (empty($periodId)) {
            return RuleResult::failed(
                $this->getName(),
                'Period ID is required for period validation',
                ['missing_field' => 'periodId']
            );
        }

        $period = $this->periodManager->getPeriod($tenantId, $periodId);

        if ($period === null) {
            return RuleResult::failed(
                $this->getName(),
                sprintf('Period %s not found', $periodId),
                ['period_id' => $periodId]
            );
        }

        if (!$this->isPeriodOpen($period)) {
            return RuleResult::failed(
                $this->getName(),
                sprintf('Period %s is not open for posting', $periodId),
                [
                    'period_id' => $periodId,
                    'status' => $this->getPeriodStatus($period),
                ]
            );
        }

        return RuleResult::passed($this->getName());
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'period_open';
    }

    /**
     * Extract tenant ID from context.
     *
     * @param object $context The context object
     * @return string The tenant ID
     */
    private function extractTenantId(object $context): string
    {
        if (method_exists($context, 'getTenantId')) {
            return $context->getTenantId();
        }

        if (property_exists($context, 'tenantId')) {
            return $context->tenantId ?? '';
        }

        if (property_exists($context, 'tenant_id')) {
            return $context->tenant_id ?? '';
        }

        return '';
    }

    /**
     * Extract period ID from context.
     *
     * @param object $context The context object
     * @return string The period ID
     */
    private function extractPeriodId(object $context): string
    {
        if (method_exists($context, 'getPeriodId')) {
            return $context->getPeriodId();
        }

        if (property_exists($context, 'periodId')) {
            return $context->periodId ?? '';
        }

        if (property_exists($context, 'period_id')) {
            return $context->period_id ?? '';
        }

        return '';
    }

    /**
     * Check if the period is open.
     *
     * @param object $period The period object
     * @return bool True if the period is open
     */
    private function isPeriodOpen(object $period): bool
    {
        if (method_exists($period, 'isOpen')) {
            return $period->isOpen();
        }

        if (method_exists($period, 'getIsOpen')) {
            return $period->getIsOpen();
        }

        if (property_exists($period, 'isOpen')) {
            return $period->isOpen;
        }

        if (property_exists($period, 'is_open')) {
            return $period->is_open;
        }

        // Check status field
        $status = $this->getPeriodStatus($period);
        return strtolower($status) === 'open';
    }

    /**
     * Get the period status.
     *
     * @param object $period The period object
     * @return string The period status
     */
    private function getPeriodStatus(object $period): string
    {
        if (method_exists($period, 'getStatus')) {
            return $period->getStatus();
        }

        if (property_exists($period, 'status')) {
            return $period->status ?? 'unknown';
        }

        return 'unknown';
    }
}