<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\PeriodOpenRuleInterface;
use Nexus\FinanceOperations\Contracts\PeriodQueryInterface;
use Nexus\FinanceOperations\DTOs\RuleContexts\PeriodOpenRuleContext;
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
final readonly class PeriodOpenRule implements PeriodOpenRuleInterface
{
    /**
     */
    public function __construct(
        private PeriodQueryInterface $periodManager,
    ) {}

    /**
     * @inheritDoc
     *
     * @return RuleResult The rule check result
     */
    public function check(PeriodOpenRuleContext $context): RuleResult
    {
        $tenantId = trim($context->tenantId);
        $periodId = trim($context->periodId);

        if ($tenantId === '') {
            return RuleResult::failed(
                $this->getName(),
                'Tenant ID is required for period validation',
                ['missing_field' => 'tenantId']
            );
        }

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