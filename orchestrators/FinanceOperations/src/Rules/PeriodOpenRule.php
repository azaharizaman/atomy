<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\PeriodStatusQueryInterface;
use Nexus\FinanceOperations\Contracts\RuleContextInterface;
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
     * @param PeriodStatusQueryInterface $periodManager Period status query dependency
     */
    public function __construct(
        private PeriodStatusQueryInterface $periodManager,
    ) {}

    /**
     * @inheritDoc
     *
     * @param RuleContextInterface $context Context containing tenantId and periodId
     * @return RuleResult The rule check result
     */
    public function check(RuleContextInterface $context): RuleResult
    {
        $tenantId = $context->getTenantId();
        $periodId = trim((string) $context->getPeriodId());

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

        if (!$period->isOpen()) {
            return RuleResult::failed(
                $this->getName(),
                sprintf('Period %s is not open for posting', $periodId),
                [
                    'period_id' => $periodId,
                    'status' => $period->getStatus(),
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
}